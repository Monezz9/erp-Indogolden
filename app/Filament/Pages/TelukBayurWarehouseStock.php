<?php

namespace App\Filament\Pages;

use App\Enums\ItemStageCode;
use App\Filament\Pages\Concerns\HasWarehouseStockView;
use BackedEnum;
use Filament\Pages\Page;

class TelukBayurWarehouseStock extends Page
{
    use HasWarehouseStockView;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected string $view = 'filament.pages.warehouse-stock-view';

    protected static ?string $title = 'Gudang TB';

    protected static ?string $navigationLabel = 'Gudang TB';

    protected static \UnitEnum|string|null $navigationGroup = 'Persediaan';

    protected static ?int $navigationSort = 6;

    protected static function warehouseCodes(): array
    {
        return ['WH-TBY', 'WH-BKS'];
    }

    protected static function warehouseNames(): array
    {
        return ['Gudang Cabang Teluk Bayur'];
    }

    protected static function fixedStageFilter(): ?string
    {
        return ItemStageCode::FinishedGoods->value;
    }
}
