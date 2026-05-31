<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\ItemCategory;
use App\Support\InventoryLabels;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Umum')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                TextInput::make('sku')
                                    ->label('Kode Item / SKU')
                                    ->required()
                                    ->maxLength(60)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('name')
                                    ->label('Nama Item')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('item_category_id')
                                    ->label('Kategori Barang')
                                    ->relationship('category', 'name')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (ItemCategory $record): string => $record->name.' - '.InventoryLabels::categoryType($record->category_type),
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Select::make('item_type')
                                    ->label('Tipe Item')
                                    ->options([
                                        'material' => 'Barang',
                                        'semi_finished' => 'Setengah Jadi',
                                        'product' => 'Produk Jadi',
                                        'packaging' => 'Kemasan / Pendukung',
                                        'service' => 'Jasa',
                                    ])
                                    ->default('material')
                                    ->required(),
                            ]),
                    ]),

                Section::make('Satuan & Harga')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                            ->schema([
                                Select::make('default_unit_id')
                                    ->label('Satuan Dasar')
                                    ->relationship('defaultUnit', 'name')
                                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->code.' - '.$record->name)
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('code')
                                            ->label('Kode Satuan')
                                            ->placeholder('Contoh: PCS, GR, KG, BALL')
                                            ->required()
                                            ->maxLength(20)
                                            ->unique('units', 'code'),
                                        TextInput::make('name')
                                            ->label('Nama Satuan')
                                            ->placeholder('Contoh: Pieces, Gram, Kilogram, Ball')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('precision')
                                            ->label('Jumlah Desimal')
                                            ->numeric()
                                            ->default(0)
                                            ->required(),
                                        Toggle::make('is_base')
                                            ->label('Satuan Dasar')
                                            ->default(true),
                                        Toggle::make('is_active')
                                            ->label('Aktif')
                                            ->default(true),
                                    ]),
                                TextInput::make('minimum_stock')
                                    ->label('Stok Minimum')
                                    ->numeric()
                                    ->step('any')
                                    ->default(0),
                                TextInput::make('purchase_price')
                                    ->label('Harga Beli')
                                    ->numeric()
                                    ->step('any')
                                    ->default(0),
                                TextInput::make('latest_weighted_avg_cost')
                                    ->label('Harga Pokok / HPP')
                                    ->numeric()
                                    ->step('any')
                                    ->default(0),
                                TextInput::make('selling_price')
                                    ->label('Harga Jual')
                                    ->numeric()
                                    ->step('any')
                                    ->default(0),
                            ]),
                    ]),

                Section::make('Produksi & Status')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])
                            ->schema([
                                Toggle::make('requires_production')
                                    ->label('Perlu Produksi')
                                    ->default(false),
                                Toggle::make('is_perishable')
                                    ->label('Mudah Rusak')
                                    ->default(false),
                                Toggle::make('is_active')
                                    ->label('Aktif / Dijual')
                                    ->default(true),
                            ]),
                        Textarea::make('description')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
