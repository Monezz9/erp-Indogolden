<?php

namespace App\Filament\Pages;

use App\Enums\BranchSaleStatus;
use App\Enums\ItemStageCode;
use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\BranchSale;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\BranchSaleService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CashierPos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected string $view = 'filament.pages.cashier-pos';

    protected static ?string $title = 'POS Kasir';

    protected static ?string $slug = 'pos-kasir';

    protected static ?string $navigationLabel = 'POS Kasir';

    protected static \UnitEnum|string|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 1;

    public ?int $branchId = null;

    public ?string $saleNumber = null;

    public ?string $cashierName = null;

    public string $paymentMethod = 'cash';

    public string $brothType = 'kuah';

    public int $spiceLevel = 1;

    public ?string $notes = null;

    public ?int $selectedItemId = null;

    public ?int $unitId = null;

    public float $qty = 1;

    public float $unitPrice = 0;

    public ?int $selectedDrinkItemId = null;

    public ?int $drinkUnitId = null;

    public float $drinkQty = 1;

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

        $this->cashierName = $user?->name;
        $this->saleNumber = $this->nextSaleNumber();
    }

    public function updatedSelectedItemId(?int $itemId): void
    {
        $item = Item::query()
            ->with('defaultUnit:id,code,name')
            ->find($itemId);

        $this->unitId = $item?->default_unit_id;
        $this->unitPrice = (float) ($item?->selling_price ?? 0);
        $this->qty = 1;
    }

    public function updatedBranchId(): void
    {
        $this->saleNumber = $this->nextSaleNumber();
        $this->reset(['selectedItemId', 'unitId', 'selectedDrinkItemId', 'drinkUnitId']);
    }

    public function updatedSelectedDrinkItemId(?int $itemId): void
    {
        $item = Item::query()
            ->with('defaultUnit:id,code,name')
            ->find($itemId);

        $this->drinkUnitId = $item?->default_unit_id;
        $this->drinkUnitPrice = (float) ($item?->selling_price ?? 0);
        $this->drinkQty = 1;
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

        $lineTotal = $this->qty * $this->unitPrice;

        $this->cart[] = [
            'item_id' => $item->id,
            'unit_id' => $this->unitId ?: $item->default_unit_id,
            'sku' => $item->sku,
            'name' => $item->name,
            'unit_name' => $item->defaultUnit?->code ?: ($item->defaultUnit?->name ?? '-'),
            'qty' => $this->qty,
            'unit_price' => $this->unitPrice,
            'line_total' => $lineTotal,
        ];

        $this->reset(['selectedItemId', 'unitId']);
        $this->qty = 1;
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

        $lineTotal = $this->drinkQty * $this->drinkUnitPrice;

        $this->cart[] = [
            'item_id' => $item->id,
            'unit_id' => $this->drinkUnitId ?: $item->default_unit_id,
            'sku' => $item->sku,
            'name' => $item->name,
            'unit_name' => $item->defaultUnit?->code ?: ($item->defaultUnit?->name ?? '-'),
            'qty' => $this->drinkQty,
            'unit_price' => $this->drinkUnitPrice,
            'line_total' => $lineTotal,
        ];

        $this->reset(['selectedDrinkItemId', 'drinkUnitId']);
        $this->drinkQty = 1;
        $this->drinkUnitPrice = 0;
    }

    public function removeItem(int $index): void
    {
        unset($this->cart[$index]);

        $this->cart = array_values($this->cart);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->discountAmount = 0;
        $this->taxAmount = 0;
        $this->notes = null;
        $this->brothType = 'kuah';
        $this->spiceLevel = 1;
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

        try {
            DB::transaction(function () use ($service, $actor): void {
                $sale = BranchSale::query()->create([
                    'sale_number' => $this->saleNumber ?: $this->nextSaleNumber(),
                    'sale_date' => now(),
                    'branch_id' => $this->branchId,
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
            });

            Notification::make()->title('Transaksi POS berhasil diposting')->success()->send();

            $this->clearCart();
            $this->saleNumber = $this->nextSaleNumber();
            $this->paymentMethod = PaymentMethod::Cash->value;
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
            'kuah' => 'Kuah',
            'nyemek' => 'Nyemek',
            'keringan' => 'Keringan',
        ];
    }

    public function selectedItem(): ?Item
    {
        if (! $this->selectedItemId) {
            return null;
        }

        return Item::query()
            ->with('defaultUnit:id,code,name')
            ->find($this->selectedItemId);
    }

    public function selectedDrinkItem(): ?Item
    {
        if (! $this->selectedDrinkItemId) {
            return null;
        }

        return Item::query()
            ->with('defaultUnit:id,code,name')
            ->find($this->selectedDrinkItemId);
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

    protected function checkoutNotes(): string
    {
        $details = [
            'Kasir: '.($this->cashierName ?: '-'),
            'Kuah: '.($this->brothTypeOptions()[$this->brothType] ?? $this->brothType),
            'Level: '.$this->spiceLevel,
        ];

        if (filled($this->notes)) {
            $details[] = 'Catatan: '.$this->notes;
        }

        return implode(PHP_EOL, $details);
    }

    protected function branchStockItemOptions(bool $includeDrinks): array
    {
        if (! $this->branchId) {
            return [];
        }

        $stageIds = $this->saleStageIds();

        return Item::query()
            ->where('is_active', true)
            ->whereHas('stockBalances', function ($query) use ($stageIds): void {
                $query->where('branch_id', $this->branchId)
                    ->where('qty_on_hand', '>', 0)
                    ->whereIn('stage_id', $stageIds);
            })
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
            ->get(['id', 'sku', 'name'])
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
            ])
            ->all();
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
