<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\FinanceExpense;
use App\Models\FinanceIncome;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\ChartWidget;

class FinanceTrendChart extends ChartWidget
{
    protected ?string $heading = 'Pemasukan vs Pengeluaran (7 Hari)';

    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::Finance->value,
            UserRole::HeadLogistics->value,
        ]);
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $branchId = ($user instanceof User && $user->isBranchLike()) ? $user->branch_id : null;

        $startDate = Carbon::today()->subDays(6)->toDateString();
        $endDate = Carbon::today()->toDateString();

        $incomeByDate = FinanceIncome::query()
            ->selectRaw('DATE(transaction_date) as trx_date, SUM(amount) as total_amount')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->groupByRaw('DATE(transaction_date)')
            ->get()
            ->mapWithKeys(fn (FinanceIncome $income): array => [(string) $income->trx_date => (float) $income->total_amount]);

        $expenseByDate = FinanceExpense::query()
            ->selectRaw('DATE(transaction_date) as trx_date, SUM(amount) as total_amount')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->groupByRaw('DATE(transaction_date)')
            ->get()
            ->mapWithKeys(fn (FinanceExpense $expense): array => [(string) $expense->trx_date => (float) $expense->total_amount]);

        $labels = [];
        $incomes = [];
        $expenses = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateKey = $date->toDateString();

            $labels[] = $date->format('d M');
            $incomes[] = (float) ($incomeByDate[$dateKey] ?? 0);
            $expenses[] = (float) ($expenseByDate[$dateKey] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $incomes,
                    'borderColor' => '#1a9b53',
                    'backgroundColor' => 'rgba(26, 155, 83, 0.15)',
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $expenses,
                    'borderColor' => '#d02626',
                    'backgroundColor' => 'rgba(208, 38, 38, 0.15)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
