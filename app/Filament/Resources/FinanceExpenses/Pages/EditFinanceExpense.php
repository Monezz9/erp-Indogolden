<?php

namespace App\Filament\Resources\FinanceExpenses\Pages;

use App\Filament\Resources\FinanceExpenses\FinanceExpenseResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecord;

class EditFinanceExpense extends EditRecord
{
    protected static string $resource = FinanceExpenseResource::class;

    protected ?string $heading = 'Edit Pengeluaran';

    protected ?string $subheading = 'Perbarui catatan biaya untuk Buku Kas.';

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
            ->label('Simpan Pengeluaran')
            ->icon('heroicon-o-check-circle')
            ->color('danger');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal')
            ->outlined()
            ->color('gray');
    }
}
