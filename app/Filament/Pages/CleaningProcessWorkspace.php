<?php

namespace App\Filament\Pages;

use App\Enums\ItemStageCode;
use App\Models\CleaningProcess;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CleaningProcessService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CleaningProcessWorkspace extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected string $view = 'filament.pages.cleaning-process-workspace';

    protected static ?string $title = 'Pembersihan Bahan';

    protected static ?string $navigationLabel = 'Pembersihan Bahan';

    protected static \UnitEnum|string|null $navigationGroup = 'Produksi';

    protected static ?int $navigationSort = 1;

    public ?string $processDate = null;

    public ?int $warehouseId = null;

    public ?int $itemId = null;

    public float $inputQty = 0;

    public float $outputQty = 0;

    public ?string $notes = null;

    /**
     * @var array<int, float|string|null>
     */
    public array $completionQty = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ($user->isAdminLike() || $user->isWarehouseLike());
    }

    public function mount(): void
    {
        $this->processDate = now()->toDateString();
        $this->warehouseId = $this->centralWarehouseId();
    }

    public function start(CleaningProcessService $service): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $item = Item::query()->findOrFail($this->itemId);

            $service->start([
                'process_date' => $this->processDate,
                'warehouse_id' => $this->warehouseId,
                'item_id' => $item->id,
                'unit_id' => $item->default_unit_id,
                'input_qty' => $this->inputQty,
                'notes' => $this->notes,
            ], $user);

            $this->reset(['itemId', 'notes']);
            $this->inputQty = 0;
            $this->processDate = now()->toDateString();

            Notification::make()->title('Grooming dimulai')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Gagal mulai grooming')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function complete(int $processId, CleaningProcessService $service): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $process = CleaningProcess::query()->findOrFail($processId);

            $service->complete($process, [
                'output_qty' => (float) ($this->completionQty[$processId] ?? 0),
            ], $user);

            unset($this->completionQty[$processId]);

            Notification::make()->title('Grooming selesai')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Gagal selesaikan grooming')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function warehouseOptions(): array
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function itemOptions(): array
    {
        return Item::query()
            ->where('is_active', true)
            ->whereHas('category', function ($query): void {
                $query->where('slug', 'raw-material')
                    ->orWhereRaw('LOWER(name) IN (?, ?)', ['raw material', 'rm']);
            })
            ->whereHas('stockBalances', function ($query): void {
                $query->where('qty_on_hand', '>', 0)
                    ->whereHas('stage', fn ($stage) => $stage->where('code', ItemStageCode::RawDirty->value))
                    ->when($this->warehouseId, fn ($q) => $q->where('warehouse_id', $this->warehouseId));
            })
            ->with('defaultUnit:id,code')
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'default_unit_id'])
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => trim(($item->sku ? $item->sku.' - ' : '').$item->name.' ('.$item->defaultUnit?->code.')'),
            ])
            ->all();
    }

    public function selectedItem(): ?Item
    {
        if (! $this->itemId) {
            return null;
        }

        return Item::query()->with('defaultUnit:id,code,name')->find($this->itemId);
    }

    public function availableQty(): float
    {
        if (! $this->itemId || ! $this->warehouseId) {
            return 0;
        }

        return (float) StockBalance::query()
            ->where('item_id', $this->itemId)
            ->where('warehouse_id', $this->warehouseId)
            ->whereHas('stage', fn ($query) => $query->where('code', ItemStageCode::RawDirty->value))
            ->sum('qty_on_hand');
    }

    public function shrinkageQty(): float
    {
        return 0;
    }

    public function shrinkagePercent(): float
    {
        return 0;
    }

    /**
     * @return Collection<int, CleaningProcess>
     */
    public function recentProcesses(): Collection
    {
        return CleaningProcess::query()
            ->with(['item.defaultUnit:id,code', 'outputItem.defaultUnit:id,code', 'warehouse'])
            ->latest('id')
            ->limit(20)
            ->get();
    }

    protected function centralWarehouseId(): ?int
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('code', 'WH-CENTRAL')
                    ->orWhere('location_type', 'central')
                    ->orWhere('name', 'Gudang Pusat');
            })
            ->orderByRaw("CASE WHEN code = 'WH-CENTRAL' THEN 0 ELSE 1 END")
            ->value('id');
    }
}
