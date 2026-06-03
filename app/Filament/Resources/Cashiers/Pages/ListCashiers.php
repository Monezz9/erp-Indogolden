<?php

namespace App\Filament\Resources\Cashiers\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\Cashiers\CashierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashiers extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = CashierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }
}
