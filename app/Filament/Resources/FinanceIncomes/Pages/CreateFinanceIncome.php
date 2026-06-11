<?php

namespace App\Filament\Resources\FinanceIncomes\Pages;

use App\Filament\Resources\FinanceIncomes\FinanceIncomeResource;
use App\Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class CreateFinanceIncome extends CreateRecord
{
    protected static string $resource = FinanceIncomeResource::class;

    protected ?string $heading = 'Buat Pemasukan';

    protected ?string $subheading = 'Catat uang masuk bisnis dan langsung masukkan ke Buku Kas.';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan Pemasukan')
            ->icon('heroicon-o-check-circle')
            ->color('success');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Simpan & Buat Lagi')
            ->icon('heroicon-o-plus-circle')
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
