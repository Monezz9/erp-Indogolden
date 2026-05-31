<?php

namespace App\Filament\Resources\ItemStages\Pages;

use App\Filament\Resources\ItemStages\ItemStageResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecord;

class EditItemStage extends EditRecord
{
    protected static string $resource = ItemStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }
}
