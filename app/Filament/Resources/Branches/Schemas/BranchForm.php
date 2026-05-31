<?php

namespace App\Filament\Resources\Branches\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode Cabang')
                    ->required()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Nama Cabang')
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')->label('No. Telepon')->maxLength(30),
                TextInput::make('email')->email()->maxLength(255),
                TextInput::make('city')->label('Kota')->maxLength(255),
                Textarea::make('address')->label('Alamat')->columnSpanFull(),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
