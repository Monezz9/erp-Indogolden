<?php

namespace App\Filament\Resources\FinanceIncomes\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\FinanceIncomes\FinanceIncomeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinanceIncomes extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = FinanceIncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }
}
