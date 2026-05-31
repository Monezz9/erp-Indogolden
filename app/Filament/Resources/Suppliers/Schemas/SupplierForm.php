<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label('Kode Supplier')->required()->maxLength(30)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nama Supplier')->required()->maxLength(255),
                TextInput::make('contact_person')->label('PIC')->maxLength(255),
                TextInput::make('phone')->label('No. Telepon')->maxLength(30),
                TextInput::make('email')->email()->maxLength(255),
                Textarea::make('address')->label('Alamat')->columnSpanFull(),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
