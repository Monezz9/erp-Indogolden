<?php

namespace App\Filament\Resources\FinanceExpenses\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\FinanceExpenses\FinanceExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinanceExpenses extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = FinanceExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }
}
