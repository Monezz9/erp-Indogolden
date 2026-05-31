<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return $this->getExcelHeaderActions();
    }
}
