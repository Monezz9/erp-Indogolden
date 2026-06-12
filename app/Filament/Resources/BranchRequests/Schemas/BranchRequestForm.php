<?php

namespace App\Filament\Resources\BranchRequests\Schemas;

use App\Enums\BranchRequestItemStatus;
use App\Enums\BranchRequestStatus;
use App\Enums\ItemStageCode;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\BranchRequest;
use App\Models\BranchRequestItem;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\User;
use App\Support\IndoNumber;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class BranchRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $isBranchUser = $user instanceof User && $user->hasAnyRole([UserRole::Branch->value, UserRole::Cabang->value]);
        $isWarehouseUser = $user instanceof User && ($user->isWarehouseLike() || $user->isAdminLike());

        return $schema
            ->columns([
                'lg' => 3,
            ])
            ->components([
                Grid::make(1)
                    ->schema([
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
                    ->description('Request akan dikirim ke gudang untuk direview dan diproses.')
                    ->icon('heroicon-o-building-storefront')
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
                                    ->live()
                                    ->dehydrated(),
                                DatePicker::make('delivery_date')
                                    ->label('Tanggal Kirim')
                                    ->required()
                                    ->default(now()->addDay())
                                    ->disabled(fn ($record): bool => self::isBranchLocked($record, $isBranchUser) || self::isWarehouseEditing($record, $isWarehouseUser))
                                    ->live()
                                    ->dehydrated(),
                            ]),
                        Textarea::make('note_branch')
                            ->label('Catatan Cabang')
                            ->placeholder('Contoh: kebutuhan untuk operasional besok pagi.')
                            ->rows(3)
                            ->disabled(fn ($record): bool => self::isBranchLocked($record, $isBranchUser) || self::isWarehouseEditing($record, $isWarehouseUser))
                            ->live(onBlur: true)
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
                    ->description('Ketik nama produk FG atau SKU, klik Tambah, lalu isi qty di daftar request.')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        TextInput::make('product_search')
                            ->label('Cari Produk')
                            ->placeholder('Ketik nama produk FG atau SKU...')
                            ->prefixIcon('heroicon-o-magnifying-glass')
                            ->live(debounce: 300)
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->extraAttributes(['class' => 'ig-branch-product-search']),
                        Placeholder::make('product_results')
                            ->hiddenLabel()
                            ->content(fn (Get $get): HtmlString => self::productPickerResults($get, $user))
                            ->visible(fn (string $operation): bool => $operation === 'create'),
                        Repeater::make('items')
                            ->label('Daftar Item Request')
                            ->relationship('items')
                            ->required()
                            ->minItems(1)
                            ->validationMessages([
                                'required' => 'Tambahkan minimal 1 item sebelum membuat request.',
                                'min' => 'Tambahkan minimal 1 item sebelum membuat request.',
                            ])
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 12,
                                ])
                                    ->schema([
                                        Placeholder::make('product_preview')
                                            ->hiddenLabel()
                                            ->content(fn (Get $get): HtmlString => self::itemPreview($get))
                                            ->visible(fn (string $operation): bool => $operation === 'create')
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 6,
                                            ]),
                                        Select::make('product_id')
                                            ->label('Produk FG')
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
                                            ->rule(self::finishedGoodsRule())
                                            ->live()
                                            ->validationMessages([
                                                'required' => 'Produk wajib dipilih.',
                                                'exists' => 'Hanya produk Finished Goods yang dapat diajukan oleh cabang.',
                                            ])
                                            ->afterStateUpdated(function (Set $set, Get $get, mixed $state) use ($user): void {
                                                $item = Item::query()
                                                    ->with(['category:id,name', 'defaultUnit:id,name'])
                                                    ->find($state);

                                                $set('unit_id', $item?->default_unit_id);
                                                $set('category', $item?->category?->name);
                                                $set('stock_available', $item ? self::availableStock($item->id, self::effectiveBranchId($get, $user)) : 0);
                                            })
                                            ->disabled(fn ($record): bool => self::isBranchLocked($record, $isBranchUser) || self::isWarehouseEditing($record, $isWarehouseUser))
                                            ->dehydrated()
                                            ->dehydratedWhenHidden()
                                            ->hidden(fn (string $operation): bool => $operation === 'create')
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => $isWarehouseUser ? 4 : 5,
                                            ]),
                                        TextInput::make('stock_available')
                                            ->label('Stok Tersedia')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated()
                                            ->suffix(fn (Get $get): ?string => Item::query()->find($get('product_id'))?->defaultUnit?->code)
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),
                                        TextInput::make('requested_qty')
                                            ->label('Qty Request')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0.001)
                                            ->placeholder('0')
                                            ->validationMessages([
                                                'required' => 'Qty request wajib diisi.',
                                                'min' => 'Qty request wajib lebih dari 0.',
                                            ])
                                            ->live(onBlur: true)
                                            ->disabled(fn ($record): bool => self::isBranchLocked($record, $isBranchUser) || self::isWarehouseEditing($record, $isWarehouseUser))
                                            ->dehydrated()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),
                                        Select::make('unit_id')
                                            ->label('Unit')
                                            ->relationship('unit', 'name')
                                            ->required()
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
                                        Hidden::make('item_status')
                                            ->default(BranchRequestItemStatus::Requested->value)
                                            ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                                    ]),
                                        Textarea::make('branch_note')
                                    ->label('Catatan')
                                    ->placeholder('Opsional')
                                    ->rows(2)
                                    ->disabled(fn ($record): bool => self::isBranchLocked($record, $isBranchUser) || self::isWarehouseEditing($record, $isWarehouseUser))
                                    ->live(onBlur: true)
                                    ->dehydrated()
                                    ->columnSpanFull(),
                                Textarea::make('warehouse_note')
                                    ->label('Catatan Gudang')
                                    ->placeholder('Opsional')
                                    ->rows(2)
                                    ->visible(fn ($record): bool => self::isWarehouseEditing($record, $isWarehouseUser))
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Tambah Item Manual')
                            ->addAction(fn ($action) => $action->label('Tambah Item Manual')->icon('heroicon-o-plus')->color('gray'))
                            ->itemLabel(fn (array $state): string => filled($state['product_id'] ?? null)
                                ? (Item::query()->find($state['product_id'])?->name ?? 'Item request')
                                : 'Item request')
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->collapsible()
                            ->addable(fn ($record, string $operation): bool => $operation !== 'create' && ! self::isBranchLocked($record, $isBranchUser) && ! self::isWarehouseEditing($record, $isWarehouseUser))
                            ->deletable(fn ($record): bool => ! self::isBranchLocked($record, $isBranchUser) && ! self::isWarehouseEditing($record, $isWarehouseUser))
                            ->columnSpanFull(),
                    ]),
                    ])
                    ->columnSpan([
                        'lg' => 2,
                    ]),
                Section::make('Ringkasan Request')
                    ->description('Preview sebelum request disimpan.')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->extraAttributes(['class' => 'ig-branch-request-summary'])
                    ->schema([
                        Placeholder::make('summary')
                            ->hiddenLabel()
                            ->content(fn (Get $get): HtmlString => self::summary($get)),
                        Placeholder::make('flow')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Alur Request</div>
                                    <ol class="mt-3 space-y-2 text-sm font-semibold text-slate-700">
                                        <li>1. Cabang buat request</li>
                                        <li>2. Gudang review</li>
                                        <li>3. Gudang approve</li>
                                        <li>4. Packing</li>
                                        <li>5. Pengiriman</li>
                                        <li>6. Cabang receive</li>
                                    </ol>
                                </div>
                            ')),
                    ])
                    ->columnSpan([
                        'lg' => 1,
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

    protected static function finishedGoodsRule(): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('items', 'id')
            ->where('is_active', true)
            ->whereIn('default_stage_id', function ($query): void {
                $query
                    ->select('id')
                    ->from('item_stages')
                    ->where('code', ItemStageCode::FinishedGoods->value);
            });
    }

    protected static function effectiveBranchId(Get $get, mixed $user): ?int
    {
        if ($user instanceof User && $user->isBranchLike()) {
            return $user->branch_id;
        }

        $branchId = $get('../../branch_id') ?? $get('branch_id');

        return filled($branchId) ? (int) $branchId : null;
    }

    protected static function availableStock(int $itemId, ?int $branchId): float
    {
        if (! $branchId) {
            return 0.0;
        }

        return (float) StockBalance::query()
            ->where('item_id', $itemId)
            ->where('branch_id', $branchId)
            ->sum('qty_on_hand');
    }

    protected static function productPickerResults(Get $get, mixed $user): HtmlString
    {
        $search = trim((string) $get('product_search'));

        if ($search === '') {
            return new HtmlString('
                <div class="ig-branch-product-results ig-branch-product-results--empty">
                    <div class="ig-branch-product-empty">
                        <strong>Mulai ketik nama produk FG atau SKU.</strong>
                        <span>Hasil pencarian akan muncul sebagai list dan bisa langsung ditambahkan.</span>
                    </div>
                </div>
            ');
        }

        $branchId = self::effectiveBranchId($get, $user);
        $selectedIds = collect($get('items') ?? [])
            ->pluck('product_id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $items = Item::query()
            ->with(['defaultUnit:id,code,name'])
            ->where('is_active', true)
            ->whereHas('defaultStage', fn (Builder $query) => $query->where('code', ItemStageCode::FinishedGoods->value))
            ->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            })
            ->orderBy('name')
            ->limit(8)
            ->get();

        if ($items->isEmpty()) {
            return new HtmlString('
                <div class="ig-branch-product-results">
                    <div class="ig-branch-product-empty">
                        <strong>Tidak ada produk FG yang cocok.</strong>
                        <span>Coba kata kunci lain atau cek master barang.</span>
                    </div>
                </div>
            ');
        }

        $rows = $items->map(function (Item $item) use ($branchId, $selectedIds): string {
            $stock = self::availableStock($item->id, $branchId);
            $unit = $item->defaultUnit?->code ?? '-';
            $isSelected = in_array($item->id, $selectedIds, true);

            return sprintf(
                '<article class="ig-branch-product-result">
                    <div class="ig-branch-product-result__main">
                        <div class="ig-branch-product-result__name">%s</div>
                        <div class="ig-branch-product-result__meta">SKU: %s | Stok: %s %s</div>
                    </div>
                    <span class="ig-branch-product-result__badge">FG</span>
                    <button type="button" wire:click="addProductToRequest(%d)" class="ig-branch-product-result__button">%s</button>
                </article>',
                e($item->name),
                e($item->sku ?: '-'),
                e(IndoNumber::decimal($stock)),
                e($unit),
                $item->id,
                $isSelected ? '+ Qty' : 'Tambah',
            );
        })->implode('');

        return new HtmlString('<div class="ig-branch-product-results">'.$rows.'</div>');
    }

    protected static function itemPreview(Get $get): HtmlString
    {
        $itemId = $get('product_id');
        $item = filled($itemId)
            ? Item::query()->with('defaultUnit:id,code,name')->find($itemId)
            : null;

        if (! $item) {
            return new HtmlString('
                <div class="ig-branch-request-line-preview">
                    <strong>Produk belum dipilih</strong>
                    <span>Gunakan pencarian produk di atas.</span>
                </div>
            ');
        }

        return new HtmlString(sprintf(
            '<div class="ig-branch-request-line-preview">
                <strong>%s</strong>
                <span>SKU: %s | Stok tersedia: %s %s</span>
            </div>',
            e($item->name),
            e($item->sku ?: '-'),
            e(IndoNumber::decimal((float) ($get('stock_available') ?? 0))),
            e($item->defaultUnit?->code ?? '-'),
        ));
    }

    protected static function summary(Get $get): HtmlString
    {
        $branch = filled($get('branch_id')) ? Branch::query()->find($get('branch_id'))?->name : '-';
        $items = collect($get('items') ?? [])->filter(fn (array $item): bool => filled($item['product_id'] ?? null));
        $totalQty = $items->sum(fn (array $item): float => (float) ($item['requested_qty'] ?? 0));
        $deliveryDate = filled($get('delivery_date')) ? date('d M Y', strtotime((string) $get('delivery_date'))) : '-';
        $note = trim((string) $get('note_branch'));
        $buttonHint = $items->isEmpty() ? 'Tambahkan minimal 1 item' : 'Siap dikirim ke gudang';
        $submitLabel = $items->isEmpty() ? 'Tambahkan minimal 1 item' : 'Kirim Request ke Gudang';
        $disabledAttribute = $items->isEmpty() ? ' disabled' : '';
        $itemModels = Item::query()
            ->with('defaultUnit:id,code')
            ->whereIn('id', $items->pluck('product_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $miniItems = $items->isEmpty()
            ? '<div class="ig-branch-summary-empty">Belum ada item dipilih</div>'
            : '<ul class="ig-branch-summary-items">'.$items->map(function (array $line) use ($itemModels): string {
                $item = $itemModels->get((int) $line['product_id']);
                $qty = (float) ($line['requested_qty'] ?? 0);

                return sprintf(
                    '<li><span>%s</span><strong>%s %s</strong></li>',
                    e($item?->name ?? 'Produk'),
                    e(IndoNumber::decimal($qty)),
                    e($item?->defaultUnit?->code ?? '-'),
                );
            })->implode('').'</ul>';

        return new HtmlString(sprintf(
            '<div class="space-y-4">
                <div class="rounded-2xl border border-red-100 bg-white p-5 shadow-sm">
                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">Draft Request</span>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-3"><dt class="text-slate-500">Cabang</dt><dd class="text-right font-bold text-slate-900">%s</dd></div>
                        <div class="flex items-start justify-between gap-3"><dt class="text-slate-500">Tanggal Kirim</dt><dd class="text-right font-bold text-slate-900">%s</dd></div>
                        <div class="flex items-start justify-between gap-3"><dt class="text-slate-500">Total Item</dt><dd class="text-right font-bold text-slate-900">%d item</dd></div>
                        <div class="flex items-start justify-between gap-3"><dt class="text-slate-500">Total Qty</dt><dd class="text-right font-bold text-slate-900">%s</dd></div>
                        <div class="flex items-start justify-between gap-3"><dt class="text-slate-500">Status</dt><dd class="text-right font-bold text-slate-900">Draft</dd></div>
                    </dl>
                    <div class="mt-4 rounded-xl bg-slate-50 p-3 text-sm text-slate-600">
                        <div class="font-bold text-slate-800">Catatan</div>
                        <div class="mt-1">%s</div>
                    </div>
                    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-3 text-sm">
                        <div class="font-bold text-slate-800">Item Request</div>
                        <div class="mt-2">%s</div>
                    </div>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">%s</div>
                <button type="button" wire:click="create" class="ig-branch-summary-submit"%s>%s</button>
            </div>',
            e($branch ?: '-'),
            e($deliveryDate),
            $items->count(),
            e(number_format($totalQty, 3, ',', '.')),
            e($note !== '' ? $note : '-'),
            $miniItems,
            e($buttonHint),
            $disabledAttribute,
            e($submitLabel),
        ));
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
