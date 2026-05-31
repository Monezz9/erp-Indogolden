<?php

namespace App\Filament\Resources\Pages;

abstract class EditRecord extends \Filament\Resources\Pages\EditRecord
{
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
