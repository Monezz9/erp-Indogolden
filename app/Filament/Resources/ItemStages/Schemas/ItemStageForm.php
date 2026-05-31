<?php

namespace App\Filament\Resources\ItemStages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ItemStageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label('Kode Tahap')->required()->maxLength(30)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nama Tahap')->required()->maxLength(255),
                TextInput::make('sequence')->label('Urutan')->required()->numeric()->default(1),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
