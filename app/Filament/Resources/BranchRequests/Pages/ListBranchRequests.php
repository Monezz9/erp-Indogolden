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

    public function getTitle(): string
    {
        return 'Request Barang Cabang';
    }

    public function getSubheading(): ?string
    {
        return 'Ajukan kebutuhan barang jadi dari cabang ke gudang pusat.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Request')
                ->icon('heroicon-o-plus')
                ->color('danger'),
            ...$this->getExcelHeaderActions(),
        ];
    }

}
