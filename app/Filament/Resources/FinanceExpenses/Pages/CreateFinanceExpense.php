<?php

namespace App\Filament\Resources\FinanceExpenses\Pages;

use App\Filament\Resources\FinanceExpenses\FinanceExpenseResource;
use App\Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class CreateFinanceExpense extends CreateRecord
{
    protected static string $resource = FinanceExpenseResource::class;

    protected ?string $heading = 'Buat Pengeluaran';

    protected ?string $subheading = 'Catat biaya operasional dengan cepat dan rapi.';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan Pengeluaran')
            ->icon('heroicon-o-check-circle')
            ->color('danger');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Simpan & Buat Lagi')
            ->icon('heroicon-o-plus-circle')
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
