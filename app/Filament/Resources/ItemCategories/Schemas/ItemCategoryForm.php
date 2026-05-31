<?php

namespace App\Filament\Resources\ItemCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ItemCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama Kategori Barang')->required()->maxLength(255),
                TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
                Select::make('category_type')
                    ->label('Kelompok Stok')
                    ->required()
                    ->options([
                        'raw_material' => 'Bahan / Material',
                        'wip' => 'Proses Produksi',
                        'finished_goods' => 'Barang Jadi',
                        'mro' => 'Operasional',
                        'analysis' => 'Analisis',
                        'other' => 'Lainnya',
                    ]),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
