<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\ProductionOrder;
use App\Models\StockMovement;
use App\Models\Transfer;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PendingApprovalsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.pending-approvals-overview';

    protected ?string $heading = 'Persetujuan Operasional';

    protected ?string $description = 'Pantau dokumen gudang, transfer, dan produksi yang menunggu keputusan.';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::HeadLogistics->value,
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string, description: string, icon: string, accent: string}>
     */
    public function getDashboardStats(): array
    {
        $movement = StockMovement::query()->where('status', 'submitted')->count();
        $transfer = Transfer::query()->where('status', 'submitted')->count();
        $production = ProductionOrder::query()->where('status', 'submitted')->count();

        return [
            [
                'label' => 'Mutasi Stok',
                'value' => (string) $movement,
                'description' => $movement > 0 ? 'Perlu dicek sebelum stok bergerak' : 'Tidak ada antrian mutasi',
                'icon' => 'heroicon-o-arrows-right-left',
                'accent' => 'from-amber-400 to-orange-500',
            ],
            [
                'label' => 'Transfer',
                'value' => (string) $transfer,
                'description' => $transfer > 0 ? 'Siap ditinjau oleh penanggung jawab' : 'Tidak ada transfer menunggu',
                'icon' => 'heroicon-o-truck',
                'accent' => 'from-sky-400 to-cyan-500',
            ],
            [
                'label' => 'Produksi',
                'value' => (string) $production,
                'description' => $production > 0 ? 'Butuh keputusan sebelum diproses' : 'Tidak ada produksi tertahan',
                'icon' => 'heroicon-o-cpu-chip',
                'accent' => 'from-emerald-400 to-green-500',
            ],
        ];
    }

    protected function getStats(): array
    {
        return array_map(
            fn (array $stat): Stat => Stat::make($stat['label'], $stat['value'])
                ->icon($stat['icon'])
                ->description($stat['description'])
                ->color('warning'),
            $this->getDashboardStats(),
        );
    }
}
