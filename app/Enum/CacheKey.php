<?php

namespace App\Enum;

enum CacheKey: string
{
    case MonthlyTotals = 'monthly_totals';
    case TopCategories = 'top_categories';
    case TransactionsYears = 'transactions_years';
    case ResentTransactions = 'recent_transactions';
}