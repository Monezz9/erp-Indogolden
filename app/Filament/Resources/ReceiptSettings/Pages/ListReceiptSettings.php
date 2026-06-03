<?php

namespace App\Filament\Resources\ReceiptSettings\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\ReceiptSettings\ReceiptSettingResource;
use App\Models\ReceiptSetting;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReceiptSettings extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = ReceiptSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => ! ReceiptSetting::query()->exists()),
            ...$this->getExcelHeaderActions(),
        ];
    }
}
