<?php

namespace App\Filament\Resources\FinanceIncomes\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\FinanceIncomes\FinanceIncomeResource;
use App\Filament\Widgets\FinanceBookOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListFinanceIncomes extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = FinanceIncomeResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceBookOverview::class,
        ];
    }
}
