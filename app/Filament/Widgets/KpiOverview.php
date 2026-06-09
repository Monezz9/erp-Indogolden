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

    protected string $view = 'filament.widgets.kpi-overview';

    protected ?string $heading = 'Ringkasan Keuangan & Stok';

    protected ?string $description = 'Gambaran cepat pemasukan, biaya, laba, dan nilai stok hari ini.';

    /**
     * @return array<int, array{label: string, value: string, description: string, icon: string, accent: string, color: string}>
     */
    public function getDashboardStats(): array
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
            [
                'label' => 'Uang Masuk',
                'value' => IndoNumber::rupiah($summary['revenue']),
                'description' => $summary['revenue'] > 0 ? 'Transaksi sudah mulai bergerak hari ini' : 'Belum ada pemasukan tercatat hari ini',
                'icon' => 'heroicon-o-banknotes',
                'accent' => 'from-emerald-400 to-green-500',
                'color' => 'success',
            ],
            [
                'label' => 'Biaya Pokok',
                'value' => IndoNumber::rupiah($summary['cogs']),
                'description' => $summary['cogs'] > 0 ? 'Biaya barang terjual hari ini' : 'Belum ada biaya pokok tercatat',
                'icon' => 'heroicon-o-calculator',
                'accent' => 'from-rose-400 to-red-500',
                'color' => 'danger',
            ],
            [
                'label' => 'Hasil Bersih',
                'value' => IndoNumber::rupiah($summary['profit']),
                'description' => $summary['profit'] >= 0 ? 'Posisi laba masih positif' : 'Perlu cek biaya dan penjualan',
                'icon' => 'heroicon-o-presentation-chart-line',
                'accent' => $summary['profit'] >= 0 ? 'from-amber-400 to-orange-500' : 'from-rose-400 to-red-500',
                'color' => $summary['profit'] >= 0 ? 'success' : 'danger',
            ],
            [
                'label' => 'Aset Stok',
                'value' => IndoNumber::rupiah($stockValue),
                'description' => 'Estimasi nilai barang yang masih tersedia',
                'icon' => 'heroicon-o-circle-stack',
                'accent' => 'from-sky-400 to-cyan-500',
                'color' => 'info',
            ],
        ];

        if ($canSeeTransfer) {
            $stats[] = [
                'label' => 'Antrian Transfer',
                'value' => (string) $pendingTransfer,
                'description' => $pendingTransfer > 0 ? 'Ada transfer yang perlu ditinjau' : 'Tidak ada transfer tertahan',
                'icon' => 'heroicon-o-truck',
                'accent' => 'from-amber-400 to-yellow-500',
                'color' => 'warning',
            ];
        }

        return $stats;
    }

    protected function getStats(): array
    {
        return array_map(
            fn (array $stat): Stat => Stat::make($stat['label'], $stat['value'])
                ->icon($stat['icon'])
                ->description($stat['description'])
                ->color($stat['color']),
            $this->getDashboardStats(),
        );
    }
}
