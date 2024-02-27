<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\DataObjects\DataTableQueryParams;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\CacheKey;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\SimpleCache\CacheInterface;

class CategoryService
{
    public function __construct(
        private readonly EntityManagerServiceInterface $entityManager,
        private readonly CacheInterface $cache,
    ) {
    }

    public function create(string $name, User $user): Category
    {
        $category = new Category();
        $category->setUser($user);

        return $this->update($category, $name, $user->getId());
    }

    public function getPaginatedCategories(DataTableQueryParams $params): Paginator
    {
        $query = $this->entityManager
            ->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->setFirstResult($params->start)
            ->setMaxResults($params->length);

        $orderBy = in_array($params->orderBy, ['name', 'createdAt', 'updatedAt']) ? $params->orderBy : 'updatedAt';
        $orderDir = strtolower($params->orderDir) === 'asc' ? 'asc' : 'desc';

        if (!empty($params->searchTerm)) {
            $query->where('c.name LIKE :name')->setParameter(
                'name',
                '%' . addcslashes($params->searchTerm, '%_') . '%'
            );
        }

        $query->orderBy('c.' . $orderBy, $orderDir);

        return new Paginator($query);
    }

    public function getById(int $id): ?Category
    {
        return $this->entityManager->find(Category::class, $id);
    }

    public function update(Category $category, string $name, int $userId): Category
    {
        $category->setName($name);

        $this->unsetCache($userId);

        return $category;
    }

    public function delete(Category $category): void
    {
        $userId = $category->getUser()->getId();

        $this->entityManager->delete($category, true);
        $this->unsetCache($userId);
    }

    public function getCategoryNames(): array
    {
        return $this->entityManager
            ->getRepository(Category::class)->createQueryBuilder('c')
            ->select('c.id', 'c.name')
            ->getQuery()
            ->getArrayResult();
    }

    public function findByName(string $name): ?Category
    {
        return $this->entityManager->getRepository(Category::class)->findBy(['name' => $name])[0] ?? null;
    }

    public function getAllKeyedByName(): array
    {
        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        $categoryMap = [];

        foreach ($categories as $category) {
            $categoryMap[strtolower($category->getName())] = $category;
        }

        return $categoryMap;
    }

    public function getTopSpendingCategories(int $userId, int $limit): array
    {
        $key = $userId . CacheKey::TopCategories->value;

        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $topSpendingCategories = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('SUM(t.amount) as total, c.name')
            ->leftJoin('t.category', 'c')
            ->where('t.amount < 0')
            ->groupBy('t.category')
            ->orderBy('total', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        $this->cache->set($key, $topSpendingCategories, 10 * 60);

        return $topSpendingCategories;
    }

    public function unsetCache(int $userId): void
    {
        $this->cache->delete($userId . CacheKey::TopCategories->value);
    }
}