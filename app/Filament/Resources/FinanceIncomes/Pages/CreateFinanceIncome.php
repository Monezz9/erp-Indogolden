<?php

namespace App\Filament\Resources\FinanceIncomes\Pages;

use App\Filament\Resources\FinanceIncomes\FinanceIncomeResource;
use App\Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateFinanceIncome extends CreateRecord
{
    protected static string $resource = FinanceIncomeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
