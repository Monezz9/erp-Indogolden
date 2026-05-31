<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\StockBalance;
use App\Models\Transfer;
use App\Models\User;
use App\Services\FinanceSummaryService;
use App\Support\IndoNumber;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class KpiOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $user = Auth::user();
        $branchId = ($user instanceof User && $user->isBranchLike()) ? $user->branch_id : null;

        $summary = app(FinanceSummaryService::class)->daily(branchId: $branchId);

        $stockValue = (float) StockBalance::query()
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->sum('total_value');

        $canSeeTransfer = $user instanceof User && $user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::HeadLogistics->value,
            UserRole::LogisticsAdmin->value,
            UserRole::Branch->value,
        ]);

        $pendingTransfer = Transfer::query()
            ->where('status', 'submitted')
            ->when($branchId, fn (Builder $query) => $query->where(function (Builder $transferQuery) use ($branchId) {
                $transferQuery
                    ->where('from_branch_id', $branchId)
                    ->orWhere('to_branch_id', $branchId);
            }))
            ->count();

        $stats = [
            Stat::make('Revenue (Daily)', IndoNumber::rupiah($summary['revenue']))
                ->description('Pemasukan hari ini')
                ->color('success'),
            Stat::make('COGS (Daily)', IndoNumber::rupiah($summary['cogs']))
                ->description('COGS hari ini')
                ->color('danger'),
            Stat::make('Profit (Daily)', IndoNumber::rupiah($summary['profit']))
                ->description('Laba bersih hari ini')
                ->color($summary['profit'] >= 0 ? 'success' : 'danger'),
            Stat::make('Total Stock Value', IndoNumber::rupiah($stockValue))
                ->description('Nilai stok terkini')
                ->color('info'),
        ];

        if ($canSeeTransfer) {
            $stats[] = Stat::make('Pending Transfer', (string) $pendingTransfer)
                ->description('Menunggu approval')
                ->color('warning');
        }

        return $stats;
    }
}
