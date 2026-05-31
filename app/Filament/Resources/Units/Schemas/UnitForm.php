<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Kode Satuan')
                    ->placeholder('Contoh: PCS, GR, KG, LTR, PACK, BALL')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Nama Satuan')
                    ->placeholder('Contoh: Pieces, Gram, Kilogram, Liter, Pack, Ball')
                    ->required()
                    ->maxLength(255),
                TextInput::make('precision')
                    ->label('Jumlah Desimal')
                    ->helperText('Isi 0 untuk satuan utuh seperti pcs/pack/ball. Isi 2-3 untuk gr/kg/ltr bila perlu pecahan.')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_base')->label('Satuan Dasar')->default(false),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
