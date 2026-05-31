<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('code')->label('Kode Gudang')->required()->maxLength(30)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nama Gudang')->required()->maxLength(255),
                Select::make('location_type')
                    ->label('Tipe Lokasi')
                    ->options([
                        'central' => 'Gudang Pusat',
                        'branch' => 'Gudang Cabang',
                        'production' => 'Area Produksi',
                    ])
                    ->required()
                    ->default('central'),
                TextInput::make('pic_name')->label('PIC')->maxLength(255),
                TextInput::make('phone')->label('No. Telepon')->maxLength(30),
                Textarea::make('address')->label('Alamat')->columnSpanFull(),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
