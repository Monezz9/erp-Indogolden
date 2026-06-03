<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasWarehouseStockView;
use BackedEnum;
use Filament\Pages\Page;

class ProductionWarehouseStock extends Page
{
    use HasWarehouseStockView;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected string $view = 'filament.pages.warehouse-stock-view';

    protected static ?string $title = 'Gudang Produksi';

    protected static ?string $navigationLabel = 'Gudang Produksi';

    protected static \UnitEnum|string|null $navigationGroup = 'Persediaan';

    protected static ?int $navigationSort = 4;

    protected static function warehouseCodes(): array
    {
        return ['WH-PROD'];
    }

    protected static function warehouseNames(): array
    {
        return ['Gudang Produksi'];
    }
}
