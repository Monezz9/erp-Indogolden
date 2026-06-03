<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasWarehouseStockView;
use BackedEnum;
use Filament\Pages\Page;

class CentralWarehouseStock extends Page
{
    use HasWarehouseStockView;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected string $view = 'filament.pages.warehouse-stock-view';

    protected static ?string $title = 'Gudang Pusat';

    protected static ?string $navigationLabel = 'Gudang Pusat';

    protected static \UnitEnum|string|null $navigationGroup = 'Persediaan';

    protected static ?int $navigationSort = 3;

    protected static function warehouseCodes(): array
    {
        return ['WH-CENTRAL'];
    }

    protected static function warehouseNames(): array
    {
        return ['Gudang Pusat'];
    }
}
