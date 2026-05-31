<?php

namespace App\Filament\Resources\ProductionOrders\Schemas;

use App\Enums\ProductionOrderStatus;
use App\Models\Item;
use App\Models\ProductionRecipe;
use App\Models\Unit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductionOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('order_number')
                    ->default(fn (): string => 'PROD-'.now()->format('YmdHis'))
                    ->visibleOn('create'),
                Hidden::make('status')
                    ->default(ProductionOrderStatus::Draft->value)
                    ->visibleOn('create'),
                Hidden::make('started_at'),
                Hidden::make('completed_at'),
                Hidden::make('actual_qty')
                    ->default(0),
                Hidden::make('shrinkage_qty')
                    ->default(0),

                Section::make('Order Produksi')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                Select::make('production_recipe_id')
                                    ->label('Resep Produksi')
                                    ->relationship('recipe', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                                        self::fillFromRecipe($set, $state, null);
                                    }),
                                DatePicker::make('planned_date')
                                    ->label('Tanggal Produksi')
                                    ->required()
                                    ->default(now()),
                                Select::make('warehouse_id')
                                    ->label('Gudang')
                                    ->relationship('warehouse', 'name')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('target_qty')
                                    ->label('Jumlah Produksi')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.001)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                                        self::fillFromRecipe($set, $get('production_recipe_id'), (float) $state);
                                    }),
                            ]),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Hasil Produksi')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                Select::make('output_item_id')
                                    ->label('Barang Jadi')
                                    ->relationship('outputItem', 'name')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                                Select::make('output_unit_id')
                                    ->label('Satuan')
                                    ->relationship('outputUnit', 'name')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ]),

                Section::make('Bahan Otomatis Dari Resep')
                    ->schema([
                        Repeater::make('inputs')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 12,
                                ])
                                    ->schema([
                                        Select::make('item_id')
                                            ->label('Bahan')
                                            ->options(fn (): array => Item::query()->orderBy('name')->pluck('name', 'id')->all())
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(['default' => 1, 'md' => 5]),
                                        Select::make('unit_id')
                                            ->label('Satuan')
                                            ->options(fn (): array => Unit::query()->orderBy('code')->pluck('code', 'id')->all())
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(['default' => 1, 'md' => 2]),
                                        TextInput::make('planned_qty')
                                            ->label('Qty Rencana')
                                            ->numeric()
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(['default' => 1, 'md' => 2]),
                                        TextInput::make('actual_qty')
                                            ->label('Qty Pakai')
                                            ->numeric()
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(['default' => 1, 'md' => 2]),
                                        Hidden::make('stage_id'),
                                        Hidden::make('unit_cost')
                                            ->default(0),
                                    ]),
                            ])
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->defaultItems(0)
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function fillFromRecipe(Set $set, mixed $recipeId, ?float $targetQty): void
    {
        if (blank($recipeId)) {
            return;
        }

        $recipe = ProductionRecipe::query()
            ->with('ingredients')
            ->find($recipeId);

        if (! $recipe) {
            return;
        }

        $targetQty = $targetQty && $targetQty > 0 ? $targetQty : (float) $recipe->output_qty;
        $ratio = $targetQty / max((float) $recipe->output_qty, 1);

        $set('output_item_id', $recipe->output_item_id);
        $set('output_unit_id', $recipe->output_unit_id);
        $set('target_qty', $targetQty);
        $set('inputs', $recipe->ingredients->map(fn ($ingredient): array => [
            'item_id' => $ingredient->item_id,
            'unit_id' => $ingredient->unit_id,
            'stage_id' => $ingredient->stage_id,
            'planned_qty' => round((float) $ingredient->qty * $ratio, 4),
            'actual_qty' => round((float) $ingredient->qty * $ratio, 4),
            'unit_cost' => 0,
        ])->all());
    }
}
