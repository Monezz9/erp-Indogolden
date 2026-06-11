<?php

namespace App\Filament\Resources\FinanceExpenses\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\FinanceExpenses\FinanceExpenseResource;
use App\Filament\Widgets\FinanceBookOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListFinanceExpenses extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = FinanceExpenseResource::class;

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
