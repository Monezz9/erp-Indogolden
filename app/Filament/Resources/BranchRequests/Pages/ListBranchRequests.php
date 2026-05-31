<?php

namespace App\Filament\Resources\BranchRequests\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\BranchRequests\BranchRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBranchRequests extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = BranchRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }
}
