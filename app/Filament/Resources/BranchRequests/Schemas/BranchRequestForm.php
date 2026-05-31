<?php

namespace App\Filament\Resources\BranchRequests\Schemas;

use App\Enums\BranchRequestItemStatus;
use App\Enums\BranchRequestStatus;
use App\Enums\ItemStageCode;
use App\Enums\UserRole;
use App\Models\BranchRequest;
use App\Models\BranchRequestItem;
use App\Models\Item;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BranchRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $isBranchUser = $user instanceof User && $user->hasAnyRole([UserRole::Branch->value, UserRole::Cabang->value]);
        $isWarehouseUser = $user instanceof User && ($user->isWarehouseLike() || $user->isAdminLike());

        return $schema
            ->components([
                Hidden::make('request_number')
                    ->default(fn (): string => 'REQ-'.now()->format('YmdHis'))
                    ->visibleOn('create'),
                Hidden::make('request_date')
                    ->default(fn (): string => now()->toDateString())
                    ->visibleOn('create'),
                Hidden::make('status')
                    ->default(BranchRequestStatus::Draft->value)
                    ->visibleOn('create'),

                Section::make('Request Cabang')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                Select::make('branch_id')
                                    ->label('Cabang')
                                    ->relationship('branch', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->default($isBranchUser ? $user?->branch_id : null)
                                    ->disabled(fn ($record): bool => $isBranchUser || self::isWarehouseEditing($record, $isWarehouseUser))
                                    ->dehydrated(),
                                DatePicker::make('delivery_date')
                                    ->label('Tanggal Kirim')
                                    ->required()
                                    ->default(now()->addDay())
                                    ->disabled(fn ($record): bool => self::isBranchLocked($record, $isBranchUser) || self::isWarehouseEditing($record, $isWarehouseUser))
                                    ->dehydrated(),
                            ]),
                        Textarea::make('note_branch')
                            ->label('Catatan Cabang')
                            ->placeholder('Contoh: kebutuhan untuk operasional besok pagi.')
                            ->rows(3)
                            ->disabled(fn ($record): bool => self::isBranchLocked($record, $isBranchUser) || self::isWarehouseEditing($record, $isWarehouseUser))
                            ->dehydrated()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make('Review Gudang')
                    ->schema([
                        Textarea::make('note_warehouse')
                            ->label('Catatan Gudang')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record): bool => self::isWarehouseEditing($record, $isWarehouseUser)),

                Section::make('Daftar Item')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship('items')
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 12,
                                ])
                                    ->schema([
                                        Select::make('product_id')
                                            ->label('Produk')
                                            ->relationship(
                                                'product',
                                                'name',
                                                fn (Builder $query) => self::branchProductQuery($query),
                                            )
                                            ->getOptionLabelFromRecordUsing(fn (Item $record): string => self::productOptionLabel($record))
                                            ->allowHtml()
                                            ->searchable(['sku', 'name'])
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                                $item = Item::query()
                                                    ->with(['category:id,name', 'defaultUnit:id,name'])
                                                    ->find($state);

                                                $set('unit_id', $item?->default_unit_id);
                                                $set('category', $item?->category?->name);
                                            })
                                            ->disabled(fn ($record): bool => self::isBranchLocked($record, $isBranchUser) || self::isWarehouseEditing($record, $isWarehouseUser))
                                            ->dehydrated()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => $isWarehouseUser ? 4 : 7,
                                            ]),
                                        TextInput::make('requested_qty')
                                            ->label('Qty Request')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0.001)
                                            ->placeholder('0')
                                            ->disabled(fn ($record): bool => self::isBranchLocked($record, $isBranchUser) || self::isWarehouseEditing($record, $isWarehouseUser))
                                            ->dehydrated()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),
                                        Select::make('unit_id')
                                            ->label('Unit')
                                            ->relationship('unit', 'name')
                                            ->disabled()
                                            ->dehydrated()
                                            ->placeholder('-')
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),
                                        TextInput::make('approved_qty')
                                            ->label('Qty Approved')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->visible(fn ($record): bool => self::isWarehouseEditing($record, $isWarehouseUser))
                                            ->disabled(fn ($record): bool => ! self::canWarehouseEditApprovedQty($record, $isWarehouseUser))
                                            ->dehydrated()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),
                                        TextInput::make('packed_qty')
                                            ->label('Qty Packing')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->visible(fn ($record): bool => self::isWarehouseEditing($record, $isWarehouseUser))
                                            ->disabled(fn ($record): bool => ! self::canWarehouseEditPackedQty($record, $isWarehouseUser))
                                            ->dehydrated()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),
                                        Hidden::make('category'),
                                        Hidden::make('shipped_qty')
                                            ->default(0)
                                            ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                                        Hidden::make('received_qty')
                                            ->default(0)
                                            ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                                        Hidden::make('stock_available')
                                            ->default(0)
                                            ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                                        Hidden::make('item_status')
                                            ->default(BranchRequestItemStatus::Requested->value)
                                            ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                                    ]),
                                Textarea::make('branch_note')
                                    ->label('Catatan Item')
                                    ->placeholder('Opsional')
                                    ->rows(2)
                                    ->disabled(fn ($record): bool => self::isBranchLocked($record, $isBranchUser) || self::isWarehouseEditing($record, $isWarehouseUser))
                                    ->dehydrated()
                                    ->columnSpanFull(),
                                Textarea::make('warehouse_note')
                                    ->label('Catatan Gudang')
                                    ->placeholder('Opsional')
                                    ->rows(2)
                                    ->visible(fn ($record): bool => self::isWarehouseEditing($record, $isWarehouseUser))
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Tambah Item')
                            ->itemLabel(fn (array $state): string => filled($state['product_id'] ?? null)
                                ? (Item::query()->find($state['product_id'])?->name ?? 'Item request')
                                : 'Item request')
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->addable(fn ($record): bool => ! self::isBranchLocked($record, $isBranchUser) && ! self::isWarehouseEditing($record, $isWarehouseUser))
                            ->deletable(fn ($record): bool => ! self::isBranchLocked($record, $isBranchUser) && ! self::isWarehouseEditing($record, $isWarehouseUser))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function branchProductQuery(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereHas('defaultStage', fn (Builder $stageQuery) => $stageQuery->where('code', ItemStageCode::FinishedGoods->value))
            ->orderBy('name');
    }

    protected static function productOptionLabel(Item $record): string
    {
        if (blank($record->sku)) {
            return e($record->name);
        }

        return sprintf(
            '<div class="flex flex-col"><span>%s</span><span class="text-xs text-gray-500 dark:text-gray-400">%s</span></div>',
            e($record->name),
            e($record->sku),
        );
    }

    protected static function isBranchLocked(mixed $record, bool $isBranchUser): bool
    {
        $request = self::resolveRequestRecord($record);

        return $isBranchUser
            && $request !== null
            && $request->status !== BranchRequestStatus::Draft->value;
    }

    protected static function isWarehouseEditing(mixed $record, bool $isWarehouseUser): bool
    {
        return $isWarehouseUser && self::resolveRequestRecord($record) !== null;
    }

    protected static function canWarehouseEditApprovedQty(mixed $record, bool $isWarehouseUser): bool
    {
        $request = self::resolveRequestRecord($record);

        return $isWarehouseUser
            && $request !== null
            && in_array($request->status, [
                BranchRequestStatus::Submitted->value,
                BranchRequestStatus::Reviewed->value,
                BranchRequestStatus::Approved->value,
            ], true);
    }

    protected static function canWarehouseEditPackedQty(mixed $record, bool $isWarehouseUser): bool
    {
        $request = self::resolveRequestRecord($record);

        return $isWarehouseUser
            && $request !== null
            && in_array($request->status, [
                BranchRequestStatus::Reviewed->value,
                BranchRequestStatus::Approved->value,
                BranchRequestStatus::Packed->value,
            ], true);
    }

    protected static function resolveRequestRecord(mixed $record): ?BranchRequest
    {
        if ($record instanceof BranchRequest) {
            return $record;
        }

        if ($record instanceof BranchRequestItem) {
            return $record->request;
        }

        return null;
    }
}
