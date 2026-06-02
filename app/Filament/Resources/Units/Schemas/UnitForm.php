<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('precision')
                    ->default(0),
                TextInput::make('code')
                    ->label('Kode Satuan')
                    ->placeholder('Contoh: PCS, GR, KG, LTR, PACK, BALL')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true)
                    ->live()
                    ->afterStateUpdated(fn (Set $set, mixed $state): mixed => $set('precision', self::precisionFromCode($state))),
                TextInput::make('name')
                    ->label('Nama Satuan')
                    ->placeholder('Contoh: Pieces, Gram, Kilogram, Liter, Pack, Ball')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_base')->label('Satuan Dasar')->default(false),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }

    protected static function precisionFromCode(mixed $code): int
    {
        return match (strtoupper(trim((string) $code))) {
            'KG' => 3,
            'GR', 'G', 'GRAM', 'LTR', 'LITER', 'ML' => 2,
            default => 0,
        };
    }
}
