<?php

namespace App\Filament\Pages;

use App\Enums\BranchSaleStatus;
use App\Enums\ItemStageCode;
use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\BranchSale;
use App\Models\Cashier;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\BranchSaleService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CashierPos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected string $view = 'filament.pages.cashier-pos';

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static ?string $title = 'KASIR';

    protected static ?string $slug = 'pos-kasir';

    protected static ?string $navigationLabel = 'POS Kasir';

    protected static \UnitEnum|string|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 1;

    public function getHeading(): string
    {
        return '';
    }

    public ?int $branchId = null;

    public ?string $saleNumber = null;

    public ?int $cashierId = null;

    public ?string $cashierName = null;

    public ?string $printReceiptUrl = null;

    public string $paymentMethod = 'cash';

    public string $brothType = 'kuah';

    public int $spiceLevel = 0;

    public string $tasteType = 'asin_gurih';

    public string $eggChoice = 'tidak_pakai';

    public ?string $notes = null;

    public ?int $selectedItemId = null;

    public ?int $unitId = null;

    public float $qty = 0;

    public float $unitPrice = 0;

    public ?int $selectedDrinkItemId = null;

    public ?int $drinkUnitId = null;

    public float $drinkQty = 0;

    public float $drinkUnitPrice = 0;

    public float $discountAmount = 0;

    public float $taxAmount = 0;

    /**
     * @var array<int, array{item_id: int, unit_id: int, sku: ?string, name: string, unit_name: string, qty: float, unit_price: float, line_total: float}>
     */
    public array $cart = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->isAdminLike()
            || ($user->isBranchLike() && filled($user->branch_id))
        );
    }

    public function mount(): void
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isBranchLike()) {
            $this->branchId = $user->branch_id;
        }

        if ($user instanceof User && $user->isAdminLike()) {
            $this->branchId = $this->defaultBranchId();
        }

        $this->cashierId = $this->defaultCashierId();
        $this->cashierName = $this->cashierId
            ? Cashier::query()->whereKey($this->cashierId)->value('name')
            : null;
        $this->saleNumber = $this->nextSaleNumber();
    }

    public function updatedSelectedItemId(?int $itemId): void
    {
        $item = Item::query()
            ->with('defaultUnit:id,code,name')
            ->find($itemId);

        $this->unitId = $item?->default_unit_id;
        $this->unitPrice = (float) ($item?->selling_price ?? 0);
        $this->qty = 0;
    }

    public function selectItem(int $itemId): void
    {
        $this->reset(['selectedDrinkItemId', 'drinkUnitId']);
        $this->drinkQty = 0;
        $this->drinkUnitPrice = 0;
        $this->selectedItemId = $itemId;
        $this->updatedSelectedItemId($itemId);
    }

    public function addMenuItemToCart(int $itemId): void
    {
        $item = Item::query()
            ->with('defaultUnit:id,code,name')
            ->where('is_active', true)
            ->find($itemId);

        if (! $item || ! $item->default_unit_id) {
            Notification::make()->title('Barang belum memiliki satuan default')->danger()->send();

            return;
        }

        $this->reset(['selectedDrinkItemId', 'drinkUnitId']);
        $this->drinkQty = 0;
        $this->drinkUnitPrice = 0;
        $this->selectedItemId = $item->id;
        $this->unitId = $item->default_unit_id;
        $this->unitPrice = (float) ($item->selling_price ?? 0);

        $this->addCartLine($item, 1, $this->unitPrice);
    }

    public function updatedBranchId(): void
    {
        $this->saleNumber = $this->nextSaleNumber();
        $this->cashierId = $this->defaultCashierId();
        $this->cashierName = $this->cashierId
            ? Cashier::query()->whereKey($this->cashierId)->value('name')
            : null;
        $this->reset(['selectedItemId', 'unitId', 'selectedDrinkItemId', 'drinkUnitId']);
    }

    public function updatedCashierId(?int $cashierId): void
    {
        $this->cashierName = $cashierId
            ? Cashier::query()->whereKey($cashierId)->value('name')
            : null;
    }

    public function updatedSelectedDrinkItemId(?int $itemId): void
    {
        $item = Item::query()
            ->with('defaultUnit:id,code,name')
            ->find($itemId);

        $this->drinkUnitId = $item?->default_unit_id;
        $this->drinkUnitPrice = (float) ($item?->selling_price ?? 0);
        $this->drinkQty = 0;
    }

    public function selectDrink(int $itemId): void
    {
        $this->reset(['selectedItemId', 'unitId']);
        $this->qty = 0;
        $this->unitPrice = 0;
        $this->selectedDrinkItemId = $itemId;
        $this->updatedSelectedDrinkItemId($itemId);
    }

    public function addDrinkItemToCart(int $itemId): void
    {
        $item = Item::query()
            ->with('defaultUnit:id,code,name')
            ->where('is_active', true)
            ->find($itemId);

        if (! $item || ! $item->default_unit_id) {
            Notification::make()->title('Minuman belum memiliki satuan default')->danger()->send();

            return;
        }

        $this->reset(['selectedItemId', 'unitId']);
        $this->qty = 0;
        $this->unitPrice = 0;
        $this->selectedDrinkItemId = $item->id;
        $this->drinkUnitId = $item->default_unit_id;
        $this->drinkUnitPrice = (float) ($item->selling_price ?? 0);

        $this->addCartLine($item, 1, $this->drinkUnitPrice);
    }

    public function addItem(): void
    {
        if (! $this->selectedItemId) {
            Notification::make()->title('Pilih barang terlebih dahulu')->warning()->send();

            return;
        }

        if ($this->qty <= 0) {
            Notification::make()->title('Qty harus lebih dari 0')->warning()->send();

            return;
        }

        if ($this->unitPrice < 0) {
            Notification::make()->title('Harga tidak boleh minus')->warning()->send();

            return;
        }

        $item = Item::query()
            ->with('defaultUnit:id,code,name')
            ->where('is_active', true)
            ->find($this->selectedItemId);

        if (! $item || ! $item->default_unit_id) {
            Notification::make()->title('Barang belum memiliki satuan default')->danger()->send();

            return;
        }

        $this->addCartLine($item, $this->qty, $this->unitPrice);

        $this->reset(['selectedItemId', 'unitId']);
        $this->qty = 0;
        $this->unitPrice = 0;
    }

    public function addDrink(): void
    {
        if (! $this->selectedDrinkItemId) {
            Notification::make()->title('Pilih minuman terlebih dahulu')->warning()->send();

            return;
        }

        if ($this->drinkQty <= 0) {
            Notification::make()->title('Qty minuman harus lebih dari 0')->warning()->send();

            return;
        }

        if ($this->drinkUnitPrice < 0) {
            Notification::make()->title('Harga minuman tidak boleh minus')->warning()->send();

            return;
        }

        $item = Item::query()
            ->with('defaultUnit:id,code,name')
            ->where('is_active', true)
            ->find($this->selectedDrinkItemId);

        if (! $item || ! $item->default_unit_id) {
            Notification::make()->title('Minuman belum memiliki satuan default')->danger()->send();

            return;
        }

        $this->addCartLine($item, $this->drinkQty, $this->drinkUnitPrice);

        $this->reset(['selectedDrinkItemId', 'drinkUnitId']);
        $this->drinkQty = 0;
        $this->drinkUnitPrice = 0;
    }

    public function removeItem(int $index): void
    {
        unset($this->cart[$index]);

        $this->cart = array_values($this->cart);
    }

    public function decrementCartItem(int $index): void
    {
        $this->adjustCartItemQty($index, -1);
    }

    public function incrementCartItem(int $index): void
    {
        $this->adjustCartItemQty($index, 1);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->discountAmount = 0;
        $this->taxAmount = 0;
        $this->notes = null;
        $this->brothType = 'kuah';
        $this->spiceLevel = 0;
        $this->tasteType = 'asin_gurih';
        $this->eggChoice = 'tidak_pakai';
    }

    public function checkout(BranchSaleService $service): void
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            return;
        }

        if ($actor->isBranchLike()) {
            $this->branchId = $actor->branch_id;
        }

        if (! $this->branchId) {
            Notification::make()->title('Pilih cabang terlebih dahulu')->warning()->send();

            return;
        }

        if ($this->cart === []) {
            Notification::make()->title('Keranjang masih kosong')->warning()->send();

            return;
        }

        if (! $this->cashierId) {
            Notification::make()->title('Pilih kasir terlebih dahulu')->warning()->send();

            return;
        }

        try {
            $createdSale = null;

            DB::transaction(function () use ($service, $actor, &$createdSale): void {
                $sale = BranchSale::query()->create([
                    'sale_number' => $this->saleNumber ?: $this->nextSaleNumber(),
                    'sale_date' => now(),
                    'branch_id' => $this->branchId,
                    'cashier_id' => $this->cashierId,
                    'status' => BranchSaleStatus::Draft,
                    'payment_method' => $this->paymentMethod,
                    'discount_amount' => $this->discountAmount,
                    'tax_amount' => $this->taxAmount,
                    'notes' => $this->checkoutNotes(),
                    'created_by' => $actor->id,
                ]);

                foreach ($this->cart as $line) {
                    $sale->items()->create([
                        'item_id' => $line['item_id'],
                        'unit_id' => $line['unit_id'],
                        'qty' => $line['qty'],
                        'unit_price' => $line['unit_price'],
                        'line_total' => $line['line_total'],
                    ]);
                }

                $service->post($sale, $actor);

                $createdSale = $sale;
            });

            Notification::make()->title('Transaksi POS berhasil diposting')->success()->send();

            $this->clearCart();
            $this->saleNumber = $this->nextSaleNumber();
            $this->paymentMethod = PaymentMethod::Cash->value;

            if ($createdSale instanceof BranchSale) {
                $this->printReceiptUrl = route('branch-sales.print.receipt', ['branchSale' => $createdSale]);
                $this->dispatch('open-receipt', url: $this->printReceiptUrl);
            }
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Transaksi POS gagal')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function branchOptions(): array
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function branchName(): string
    {
        return Branch::query()->whereKey($this->branchId)->value('name') ?? '-';
    }

    public function cashierOptions(): array
    {
        return Cashier::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('branch_id');

                if ($this->branchId) {
                    $query->orWhere('branch_id', $this->branchId);
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function itemOptions(): array
    {
        return $this->branchStockItemOptions(includeDrinks: false);
    }

    public function drinkOptions(): array
    {
        return $this->branchStockItemOptions(includeDrinks: true);
    }

    public function paymentMethodOptions(): array
    {
        return PaymentMethod::options();
    }

    public function brothTypeOptions(): array
    {
        return [
            'nyemek' => 'Nyemek',
            'kuah' => 'Berkuah',
        ];
    }

    public function spiceLevelOptions(): array
    {
        return [
            0 => 'Zero',
            1 => 'Mild',
            2 => 'Medium',
            3 => 'Hot',
        ];
    }

    public function tasteTypeOptions(): array
    {
        return [
            'asin_gurih' => 'Asin Gurih',
            'gurih_manis' => 'Gurih Manis',
        ];
    }

    public function eggChoiceOptions(): array
    {
        return [
            'tidak_pakai' => 'Tidak Pakai',
            'telur_orak_arik' => 'Telur Orak Arik',
            'telur_utuh' => 'Telur Utuh',
        ];
    }

    public function selectedItem(): ?Item
    {
        if (! $this->selectedItemId) {
            return null;
        }

        return Item::query()
            ->with(['category:id,name,slug,category_type', 'defaultUnit:id,code,name'])
            ->find($this->selectedItemId);
    }

    public function selectedDrinkItem(): ?Item
    {
        if (! $this->selectedDrinkItemId) {
            return null;
        }

        return Item::query()
            ->with(['category:id,name,slug,category_type', 'defaultUnit:id,code,name'])
            ->find($this->selectedDrinkItemId);
    }

    /**
     * @return Collection<int, Item>
     */
    public function menuItems(): Collection
    {
        return $this->branchStockItems(includeDrinks: false);
    }

    /**
     * @return Collection<int, Item>
     */
    public function drinkItems(): Collection
    {
        return $this->branchStockItems(includeDrinks: true);
    }

    public function categoryShortLabel(?Item $item): string
    {
        if (! $item?->category) {
            return '-';
        }

        return $item->category->category_type === 'finished_goods'
            ? 'FG'
            : $item->category->name;
    }

    public function subtotal(): float
    {
        return array_sum(array_column($this->cart, 'line_total'));
    }

    public function total(): float
    {
        return max(0, $this->subtotal() - $this->discountAmount + $this->taxAmount);
    }

    /**
     * @return Collection<int, BranchSale>
     */
    public function recentSales(): Collection
    {
        return BranchSale::query()
            ->with('branch:id,name')
            ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
            ->latest('id')
            ->limit(8)
            ->get();
    }

    protected function nextSaleNumber(): string
    {
        $branch = Branch::query()->find($this->branchId);
        $branchCode = $this->saleBranchCode($branch);
        $prefix = $branchCode.'-'.now()->format('ym').($this->branchId ?: 0);
        $last = BranchSale::query()
            ->where('sale_number', 'like', $prefix.'-%')
            ->latest('id')
            ->value('sale_number');

        $next = is_string($last) ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    protected function saleBranchCode(?Branch $branch): string
    {
        $code = Str::upper((string) ($branch?->code ?: 'POS'));
        $code = preg_replace('/[^A-Z0-9]/', '', $code) ?: 'POS';

        return Str::limit($code, 6, '');
    }

    protected function defaultBranchId(): ?int
    {
        return StockBalance::query()
                ->whereNotNull('branch_id')
                ->where('qty_on_hand', '>', 0)
                ->whereHas('stage', fn ($query) => $query->whereIn('code', [
                    ItemStageCode::BranchStock->value,
                    ItemStageCode::Mro->value,
                ]))
                ->value('branch_id')
            ?? Branch::query()
                ->where('is_active', true)
                ->value('id');
    }

    protected function defaultCashierId(): ?int
    {
        return Cashier::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('branch_id');

                if ($this->branchId) {
                    $query->orWhere('branch_id', $this->branchId);
                }
            })
            ->orderByRaw('case when branch_id is null then 1 else 0 end')
            ->orderBy('name')
            ->value('id');
    }

    protected function checkoutNotes(): string
    {
        $custom = $this->currentSeblakCustom();
        $details = [
            'Kasir: '.($this->cashierName ?: '-'),
            'Custom Seblak:',
            'Kuah: '.$custom['Kuah'],
            'Level: '.$custom['Level'],
            'Rasa: '.$custom['Rasa'],
        ];

        if (isset($custom['Telur'])) {
            $details[] = 'Telur: '.$custom['Telur'];
        }

        if (filled($this->notes)) {
            $details[] = 'Catatan: '.$this->notes;
        }

        return implode(PHP_EOL, $details);
    }

    public function customSeblakSummary(): string
    {
        $custom = $this->currentSeblakCustom();

        return implode(' • ', array_filter([
            $custom['Kuah'],
            $custom['Level'],
            $custom['Rasa'],
            $custom['Telur'] ?? null,
        ]));
    }

    protected function addCartLine(Item $item, float $qty, float $unitPrice): void
    {
        foreach ($this->cart as $index => $line) {
            if ((int) $line['item_id'] !== $item->id) {
                continue;
            }

            $newQty = (float) $line['qty'] + $qty;
            $this->cart[$index]['qty'] = $newQty;
            $this->cart[$index]['line_total'] = $newQty * (float) $line['unit_price'];

            return;
        }

        $this->cart[] = [
            'item_id' => $item->id,
            'unit_id' => $item->default_unit_id,
            'sku' => $item->sku,
            'name' => $item->name,
            'unit_name' => $item->defaultUnit?->code ?: ($item->defaultUnit?->name ?? '-'),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => $qty * $unitPrice,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function currentSeblakCustom(): array
    {
        $custom = [
            'Kuah' => $this->brothTypeOptions()[$this->brothType] ?? $this->brothType,
            'Level' => $this->spiceLevelOptions()[$this->spiceLevel] ?? (string) $this->spiceLevel,
            'Rasa' => $this->tasteTypeOptions()[$this->tasteType] ?? $this->tasteType,
        ];

        if ($this->eggChoice !== 'tidak_pakai') {
            $custom['Telur'] = str_replace('Telur ', '', $this->eggChoiceOptions()[$this->eggChoice] ?? $this->eggChoice);
        }

        if (filled($this->notes)) {
            $custom['Catatan'] = (string) $this->notes;
        }

        return $custom;
    }

    protected function adjustCartItemQty(int $index, float $delta): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $qty = max(1, ((float) $this->cart[$index]['qty']) + $delta);

        $this->cart[$index]['qty'] = $qty;
        $this->cart[$index]['line_total'] = $qty * (float) $this->cart[$index]['unit_price'];
    }

    protected function branchStockItemOptions(bool $includeDrinks): array
    {
        return $this->branchStockItems($includeDrinks)
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => $item->name,
            ])
            ->all();
    }

    /**
     * @return Collection<int, Item>
     */
    protected function branchStockItems(bool $includeDrinks): Collection
    {
        if (! $this->branchId) {
            return collect();
        }

        $stageIds = $this->saleStageIds();

        return Item::query()
            ->where('is_active', true)
            ->when(
                $includeDrinks,
                fn ($query) => $query->whereHas('stockBalances', function ($query) use ($stageIds): void {
                    $query->where('branch_id', $this->branchId)
                        ->where('qty_on_hand', '>', 0)
                        ->whereIn('stage_id', $stageIds);
                }),
            )
            ->when(
                ! $includeDrinks,
                fn ($query) => $query->whereHas(
                    'category',
                    fn ($category) => $category
                        ->where('slug', 'finished-goods')
                        ->orWhere('category_type', 'finished_goods'),
                ),
            )
            ->where(function ($query) use ($includeDrinks): void {
                $drinkMatcher = function ($q): void {
                    $q->where('name', 'like', '%minuman%')
                        ->orWhere('name', 'like', '%drink%')
                        ->orWhere('name', 'like', '%es %')
                        ->orWhere('name', 'like', '%teh%')
                        ->orWhere('name', 'like', '%kopi%')
                        ->orWhere('name', 'like', '%air%')
                        ->orWhereHas('category', function ($category): void {
                            $category->where('name', 'like', '%minuman%')
                                ->orWhere('slug', 'like', '%minuman%')
                                ->orWhere('category_type', 'like', '%drink%');
                        });
                };

                if ($includeDrinks) {
                    $query->where($drinkMatcher);

                    return;
                }

                $query->where('name', 'not like', '%minuman%')
                    ->where('name', 'not like', '%drink%')
                    ->where('name', 'not like', '%es %')
                    ->where('name', 'not like', '%teh%')
                    ->where('name', 'not like', '%kopi%')
                    ->where('name', 'not like', '%air%')
                    ->whereDoesntHave('category', function ($category): void {
                        $category->where('name', 'like', '%minuman%')
                            ->orWhere('slug', 'like', '%minuman%')
                            ->orWhere('category_type', 'like', '%drink%');
                    });
            })
            ->orderBy('name')
            ->with(['category:id,name,category_type', 'defaultUnit:id,code,name'])
            ->get(['id', 'sku', 'name', 'item_category_id', 'default_unit_id', 'selling_price']);
    }

    /**
     * @return array<int>
     */
    protected function saleStageIds(): array
    {
        return StockBalance::query()
            ->where('branch_id', $this->branchId)
            ->where('qty_on_hand', '>', 0)
            ->whereHas('stage', fn ($query) => $query->whereIn('code', [
                ItemStageCode::BranchStock->value,
                ItemStageCode::Mro->value,
            ]))
            ->distinct()
            ->pluck('stage_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
