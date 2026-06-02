<?php

namespace App\Filament\Resources\Items\Schemas;

use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Support\InventoryLabels;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('item_type')
                    ->default('material'),
                Hidden::make('default_stage_id'),
                Hidden::make('requires_production')
                    ->default(false),
                Hidden::make('latest_weighted_avg_cost')
                    ->default(0),
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
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                                        $itemType = self::itemTypeFromCategory($state);

                                        $set('item_type', $itemType);
                                        $set('default_stage_id', self::stageIdFromCategory($state));
                                        $set('requires_production', in_array($itemType, ['semi_finished', 'product'], true));
                                    }),
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
                                        Hidden::make('precision')
                                            ->default(0),
                                        TextInput::make('code')
                                            ->label('Kode Satuan')
                                            ->placeholder('Contoh: PCS, GR, KG, BALL')
                                            ->required()
                                            ->maxLength(20)
                                            ->unique('units', 'code')
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set, mixed $state): mixed => $set('precision', self::unitPrecisionFromCode($state))),
                                        TextInput::make('name')
                                            ->label('Nama Satuan')
                                            ->placeholder('Contoh: Pieces, Gram, Kilogram, Ball')
                                            ->required()
                                            ->maxLength(255),
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
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, mixed $state): mixed => $set('latest_weighted_avg_cost', (float) $state)),
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

    protected static function itemTypeFromCategory(mixed $categoryId): string
    {
        $categoryType = ItemCategory::query()->whereKey($categoryId)->value('category_type');

        return match ($categoryType) {
            'wip', 'analysis' => 'semi_finished',
            'finished_goods' => 'product',
            'mro' => 'packaging',
            default => 'material',
        };
    }

    protected static function stageIdFromCategory(mixed $categoryId): ?int
    {
        $categoryType = ItemCategory::query()->whereKey($categoryId)->value('category_type');

        $stageCode = match ($categoryType) {
            'wip' => 'wip',
            'finished_goods' => 'finished_goods',
            'mro' => 'mro',
            'analysis' => 'analysis',
            default => 'raw_dirty',
        };

        return ItemStage::query()->where('code', $stageCode)->value('id');
    }

    protected static function unitPrecisionFromCode(mixed $code): int
    {
        return match (strtoupper(trim((string) $code))) {
            'KG' => 3,
            'GR', 'G', 'GRAM', 'LTR', 'LITER', 'ML' => 2,
            default => 0,
        };
    }
}
