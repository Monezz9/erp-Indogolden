<?php

namespace App\Services;

use App\Models\FinanceExpense;
use App\Models\FinanceIncome;
use Illuminate\Support\Carbon;

class FinanceSummaryService
{
    /**
     * @return array<string, float>
     */
    public function daily(?Carbon $date = null, ?int $branchId = null): array
    {
        $date ??= now();

        $incomeQuery = FinanceIncome::query()->whereDate('transaction_date', $date->toDateString());
        $expenseQuery = FinanceExpense::query()->whereDate('transaction_date', $date->toDateString());

        if ($branchId !== null) {
            $incomeQuery->where('branch_id', $branchId);
            $expenseQuery->where('branch_id', $branchId);
        }

        $revenue = (float) $incomeQuery->sum('amount');
        $expense = (float) $expenseQuery->sum('amount');

        $cogs = (float) FinanceExpense::query()
            ->whereDate('transaction_date', $date->toDateString())
            ->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn ($q) => $q->where('is_cogs', true))
            ->sum('amount');

        return [
            'revenue' => $revenue,
            'expense' => $expense,
            'cogs' => $cogs,
            'profit' => $revenue - $expense,
        ];
    }

    /**
     * @return array<string, float>
     */
    public function monthly(int $year, int $month, ?int $branchId = null): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $incomeQuery = FinanceIncome::query()->whereBetween('transaction_date', [$start, $end]);
        $expenseQuery = FinanceExpense::query()->whereBetween('transaction_date', [$start, $end]);

        if ($branchId !== null) {
            $incomeQuery->where('branch_id', $branchId);
            $expenseQuery->where('branch_id', $branchId);
        }

        $revenue = (float) $incomeQuery->sum('amount');
        $expense = (float) $expenseQuery->sum('amount');

        $cogs = (float) FinanceExpense::query()
            ->whereBetween('transaction_date', [$start, $end])
            ->when($branchId !== null, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('category', fn ($q) => $q->where('is_cogs', true))
            ->sum('amount');

        return [
            'revenue' => $revenue,
            'expense' => $expense,
            'cogs' => $cogs,
            'profit' => $revenue - $expense,
        ];
    }
}
