<?php

namespace App\Filament\Resources\HppCalculations\Pages;

use App\Filament\Resources\HppCalculations\HppCalculationResource;
use App\Models\User;
use App\Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateHppCalculation extends CreateRecord
{
    protected static string $resource = HppCalculationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $data['created_by'] = $user->id;
            $data['branch_id'] = $data['branch_id'] ?? $user->branch_id;
        }

        $data['calc_number'] = $data['calc_number'] ?? 'HPP-'.now()->format('YmdHis');

        return $data;
    }
}
