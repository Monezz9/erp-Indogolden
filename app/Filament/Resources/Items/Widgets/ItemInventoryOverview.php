<?php

namespace App\Filament\Resources\Items\Widgets;

use App\Enums\ItemStageCode;
use App\Models\Item;
use App\Support\InventoryStockStatus;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ItemInventoryOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.resources.items.widgets.inventory-summary-cards';

    protected int | array | null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 5,
    ];

    /**
     * @return array<int, array{label: string, value: string, description: string, icon: string, tone: string}>
     */
    public function inventorySummaryCards(): array
    {
        return [
            [
                'label' => 'Total Item',
                'value' => (string) Item::query()->count(),
                'description' => 'Semua master barang',
                'icon' => 'heroicon-o-cube',
                'tone' => 'total',
            ],
            [
                'label' => 'Finished Goods',
                'value' => (string) $this->stageCount(ItemStageCode::FinishedGoods),
                'description' => 'Siap jual',
                'icon' => 'heroicon-o-shopping-bag',
                'tone' => 'finished',
            ],
            [
                'label' => 'Raw Material',
                'value' => (string) $this->rawMaterialCount(),
                'description' => 'Bahan baku aktif',
                'icon' => 'heroicon-o-beaker',
                'tone' => 'raw',
            ],
            [
                'label' => 'Raw Clean',
                'value' => (string) $this->stageCount(ItemStageCode::RawClean),
                'description' => 'Siap diproses',
                'icon' => 'heroicon-o-sparkles',
                'tone' => 'clean',
            ],
            [
                'label' => 'Stok Kritis',
                'value' => (string) $this->criticalStockCount(),
                'description' => 'Perlu restock',
                'icon' => 'heroicon-o-exclamation-triangle',
                'tone' => 'critical',
            ],
        ];
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Item', (string) Item::query()->count())
                ->description('Semua master barang')
                ->icon('heroicon-o-cube')
                ->color('gray'),
            Stat::make('Finished Goods', (string) $this->stageCount(ItemStageCode::FinishedGoods))
                ->description('FG aktif siap jual')
                ->icon('heroicon-o-shopping-bag')
                ->color('success'),
            Stat::make('Raw Material', (string) $this->rawMaterialCount())
                ->description('Bahan baku aktif')
                ->icon('heroicon-o-beaker')
                ->color('warning'),
            Stat::make('Raw Clean', (string) $this->stageCount(ItemStageCode::RawClean))
                ->description('RC aktif siap diproses')
                ->icon('heroicon-o-sparkles')
                ->color('info'),
            Stat::make('Stok Kritis', (string) $this->criticalStockCount())
                ->description('Stok berada di bawah minimum')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }

    protected function stageCount(ItemStageCode $stage): int
    {
        return Item::query()
            ->where('is_active', true)
            ->whereHas('defaultStage', fn (Builder $query): Builder => $query->where('code', $stage->value))
            ->count();
    }

    protected function rawMaterialCount(): int
    {
        return Item::query()
            ->where('is_active', true)
            ->whereHas(
                'defaultStage',
                fn (Builder $query): Builder => $query->where('code', ItemStageCode::RawDirty->value),
            )
            ->count();
    }

    protected function criticalStockCount(): int
    {
        return Item::query()
            ->withSum('stockBalances as stock_qty', 'qty_on_hand')
            ->get(['id', 'minimum_stock'])
            ->filter(function (Item $item): bool {
                $minimum = (float) $item->minimum_stock;
                $stock = (float) ($item->stock_qty ?? 0);

                return InventoryStockStatus::status($stock, $minimum) === InventoryStockStatus::CRITICAL;
            })
            ->count();
    }
}
