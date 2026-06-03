<?php

namespace App\Filament\Resources\Cashiers\Pages;

use App\Filament\Resources\Cashiers\CashierResource;
use App\Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;

class EditCashier extends EditRecord
{
    protected static string $resource = CashierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }
}
