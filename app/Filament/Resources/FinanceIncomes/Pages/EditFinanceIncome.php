<?php

namespace App\Filament\Resources\FinanceIncomes\Pages;

use App\Filament\Resources\FinanceIncomes\FinanceIncomeResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecord;

class EditFinanceIncome extends EditRecord
{
    protected static string $resource = FinanceIncomeResource::class;

    protected ?string $heading = 'Edit Pemasukan';

    protected ?string $subheading = 'Perbarui catatan uang masuk untuk Buku Kas.';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Pemasukan')
            ->icon('heroicon-o-check-circle')
            ->color('success');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal')
            ->outlined()
            ->color('gray');
    }
}
