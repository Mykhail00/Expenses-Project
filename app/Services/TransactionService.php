<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\DataObjects\DataTableQueryParams;
use App\DataObjects\TransactionData;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\CacheKey;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\SimpleCache\CacheInterface;

class TransactionService
{
    public function __construct(
        private readonly EntityManagerServiceInterface $entityManager,
        private readonly CacheInterface $cache,
    ) {
    }

    public function create(TransactionData $transactionData, User $user): Transaction
    {
        $transaction = new Transaction();

        $transaction->setUser($user);

        return $this->update($transaction, $transactionData, $user->getId());
    }

    public function getPaginatedTransactions(DataTableQueryParams $params): Paginator
    {
        $query = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('t', 'c', 'r')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.receipts', 'r')
            ->setFirstResult($params->start)
            ->setMaxResults($params->length);

        $orderBy = in_array($params->orderBy, ['description', 'amount', 'date', 'category'])
            ? $params->orderBy
            : 'date';
        $orderDir = strtolower($params->orderDir) === 'asc' ? 'asc' : 'desc';

        if (!empty($params->searchTerm)) {
            $query->where('t.description LIKE :description')
                ->setParameter('description', '%' . addcslashes($params->searchTerm, '%_') . '%');
        }

        if ($orderBy === 'category') {
            $query->orderBy('c.name', $orderDir);
        } else {
            $query->orderBy('t.' . $orderBy, $orderDir);
        }

        return new Paginator($query);
    }

    public function getById(int $id): ?Transaction
    {
        return $this->entityManager->find(Transaction::class, $id);
    }

    public function update(Transaction $transaction, TransactionData $transactionData, int $userId): Transaction
    {
        $transaction->setDescription($transactionData->description);
        $transaction->setAmount($transactionData->amount);
        $transaction->setDate($transactionData->date);
        $transaction->setCategory($transactionData->category);

        $this->unsetCache($userId, (int) $transactionData->date->format('Y'));

        return $transaction;
    }

    public function delete(Transaction $transaction): void
    {
        $year = (int) $transaction->getDate()->format('Y');
        $userId = $transaction->getUser()->getId();

        $this->entityManager->delete($transaction, true);
        $this->unsetCache($userId, $year);
    }

    public function toggleReviewed(Transaction $transaction): void
    {
        $transaction->setReviewed(!$transaction->wasReviewed());
    }

    public function getTotals(\DateTime $startDate, \DateTime $endDate): array
    {
        $startDate->setTime(0, 0);
        $endDate->setTime(23, 59, 59);

        $totals = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select(
                '
            SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) AS income,
            SUM(CASE WHEN t.amount < 0 THEN t.amount ELSE 0 END) AS expenses
            '
            )
            ->where('t.date >= :start')
            ->andWhere('t.date <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getArrayResult();

        $income = $totals[0]['income'] ?? 0;
        $expenses = $totals[0]['expenses'] ?? 0;
        $net = $income + $expenses;

        return ['net' => $net, 'income' => $income, 'expense' => $expenses];
    }

    public function getRecentTransactions(int $userId, int $limit): array
    {
        $key = $userId . CacheKey::ResentTransactions->value;

        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $transactions = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('t.description', 't.amount', 't.date', 'c.name AS categoryName')
            ->leftJoin('t.category', 'c')
            ->orderBy('t.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        $this->cache->set($key, $transactions, 10 * 60);

        return $transactions;
    }

    public function getMonthlySummary(int $userId, int $year): array
    {
        $key = $userId . $year . CacheKey::MonthlyTotals->value;

        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $monthlyTotals = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select(
                'MONTH(t.date) AS m,
            SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) AS income,
            ABS(SUM(CASE WHEN t.amount < 0 THEN t.amount ELSE 0 END)) AS expense
            '
            )
            ->where('YEAR(t.date) = :year')
            ->setParameter('year', $year)
            ->groupBy('m')
            ->getQuery()
            ->getArrayResult();

        $this->cache->set($key, $monthlyTotals, 10 * 60);

        return $monthlyTotals;
    }

    public function getTransactionsYears(int $userId): array
    {
        $key = $userId . CacheKey::TransactionsYears->value;

        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $yearsArray = [];
        $queryResults = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('YEAR(t.date) as year')
            ->distinct()
            ->orderBy('year', 'DESC')
            ->getQuery()
            ->getArrayResult();

        foreach ($queryResults as $value) {
            $yearsArray[] = $value['year'];
        }

        $this->cache->set($key, $yearsArray, 10 * 60);

        return $yearsArray;
    }

    private function unsetCache(int $userId, int $year): void
    {
        $monthlyTotals = $userId . $year . CacheKey::MonthlyTotals->value;
        $topCategories = $userId . CacheKey::TopCategories->value;
        $transactionsYears = $userId . CacheKey::TransactionsYears->value;
        $recentTransactions = $userId . CacheKey::ResentTransactions->value;

        $this->cache->deleteMultiple([
            $monthlyTotals,
            $topCategories,
            $transactionsYears,
            $recentTransactions,
        ]);
    }
}