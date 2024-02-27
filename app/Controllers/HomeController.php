<?php

declare(strict_types=1);

namespace App\Controllers;

use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\TransactionService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class HomeController
{
    public function __construct(
        private readonly Twig $twig,
        private readonly TransactionService $transactionService,
        private readonly CategoryService $categoryService,
        private readonly ResponseFormatter $responseFormatter,
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user')->getId();
        $transactionsYears = $this->transactionService->getTransactionsYears($userId);
        $recentTransactions = $this->transactionService->getRecentTransactions($userId, 10);
        $topSpendingCategories = $this->categoryService->getTopSpendingCategories($userId, 4);

        return $this->twig->render(
            $response,
            'dashboard.twig',
            [
                'transactions' => $recentTransactions,
                'transactionsYears' => $transactionsYears,
                'topSpendingCategories' => $topSpendingCategories,
            ]
        );
    }

    public function getYearToDateStatistics(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $year = (int) $params['year'];

        $userId = $request->getAttribute('user')->getId();
        $data = $this->transactionService->getMonthlySummary($userId, $year);

        return $this->responseFormatter->asJson($response, $data);
    }

    public function getTotals(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $startDate = \DateTime::createFromFormat('Y-m-d', $params['start']);
        $endDate = \DateTime::createFromFormat('Y-m-d', $params['end']);
        $totals = $this->transactionService->getTotals($startDate, $endDate);

        return $this->responseFormatter->asJson($response, $totals);
    }
}