<?php

namespace App\Filament\Pages;

use App\Enums\ItemStageCode;
use App\Filament\Pages\Concerns\HasWarehouseStockView;
use BackedEnum;
use Filament\Pages\Page;

class CandiPanggungWarehouseStock extends Page
{
    use HasWarehouseStockView;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected string $view = 'filament.pages.warehouse-stock-view';

    protected static ?string $title = 'Gudang CP';

    protected static ?string $navigationLabel = 'Gudang CP';

    protected static \UnitEnum|string|null $navigationGroup = 'Persediaan';

    protected static ?int $navigationSort = 5;

    protected static function warehouseCodes(): array
    {
        return ['WH-CPG', 'WH-JKT'];
    }

    protected static function warehouseNames(): array
    {
        return ['Gudang Cabang Candi Panggung'];
    }

    protected static function fixedStageFilter(): ?string
    {
        return ItemStageCode::FinishedGoods->value;
    }
}
