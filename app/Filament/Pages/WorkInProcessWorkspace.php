<?php

namespace App\Filament\Pages;

use App\Enums\ItemStageCode;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkInProcess;
use App\Services\WorkInProcessService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class WorkInProcessWorkspace extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected string $view = 'filament.pages.work-in-process-workspace';

    protected static ?string $title = 'WORK IN PROCESS';

    protected static ?string $navigationLabel = 'WORK IN PROCESS';

    protected static \UnitEnum|string|null $navigationGroup = 'Produksi';

    protected static ?int $navigationSort = 2;

    public string $processType = 'internal';

    public ?string $processDate = null;

    public ?int $warehouseId = null;

    public ?int $inputItemId = null;

    public ?int $outputItemId = null;

    public float $inputQty = 0;

    public float $standardConversionPerUnit = 0;

    public float $actualOutputQty = 0;

    public float $overheadCost = 0;

    public ?string $notes = null;

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

    public function setProcessType(string $type): void
    {
        if (! in_array($type, ['internal', 'vendor'], true)) {
            return;
        }

        $this->processType = $type;
    }

    public function post(WorkInProcessService $service): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $inputItem = Item::query()->findOrFail($this->inputItemId);
            $outputItem = Item::query()->findOrFail($this->outputItemId);

            $service->post([
                'process_date' => $this->processDate,
                'process_type' => $this->processType,
                'warehouse_id' => $this->warehouseId,
                'input_item_id' => $inputItem->id,
                'output_item_id' => $outputItem->id,
                'input_unit_id' => $inputItem->default_unit_id,
                'output_unit_id' => $outputItem->default_unit_id,
                'input_qty' => $this->inputQty,
                'standard_conversion_per_unit' => $this->standardConversionPerUnit,
                'actual_output_qty' => $this->actualOutputQty,
                'overhead_cost' => $this->overheadCost,
                'notes' => $this->notes,
            ], $user);

            $this->reset(['inputItemId', 'outputItemId', 'notes']);
            $this->inputQty = 0;
            $this->standardConversionPerUnit = 0;
            $this->actualOutputQty = 0;
            $this->overheadCost = 0;
            $this->processDate = now()->toDateString();

            Notification::make()->title('Work In Process berhasil diposting')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Gagal posting Work In Process')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function inputItemOptions(): array
    {
        return Item::query()
            ->where('is_active', true)
            ->where('item_type', '!=', 'premix')
            ->whereHas('defaultStage', fn ($stage) => $stage->whereIn('code', $this->inputStageCodes()))
            ->whereHas('stockBalances', function ($query): void {
                $query->where('qty_on_hand', '>', 0)
                    ->whereHas('stage', fn ($stage) => $stage->whereIn('code', $this->inputStageCodes()))
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

    public function outputItemOptions(): array
    {
        return Item::query()
            ->where('is_active', true)
            ->whereHas('defaultStage', fn ($stage) => $stage->where('code', ItemStageCode::FinishedGoods->value))
            ->with('defaultUnit:id,code')
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'default_unit_id'])
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => trim(($item->sku ? $item->sku.' - ' : '').$item->name.' ('.$item->defaultUnit?->code.')'),
            ])
            ->all();
    }

    public function selectedInputItem(): ?Item
    {
        return $this->inputItemId
            ? Item::query()->with('defaultUnit:id,code,name')->find($this->inputItemId)
            : null;
    }

    public function selectedOutputItem(): ?Item
    {
        return $this->outputItemId
            ? Item::query()->with('defaultUnit:id,code,name')->find($this->outputItemId)
            : null;
    }

    public function availableQty(): float
    {
        if (! $this->inputItemId || ! $this->warehouseId) {
            return 0;
        }

        return (float) StockBalance::query()
            ->where('item_id', $this->inputItemId)
            ->where('warehouse_id', $this->warehouseId)
            ->whereHas('stage', fn ($query) => $query->whereIn('code', $this->inputStageCodes()))
            ->sum('qty_on_hand');
    }

    public function expectedOutputQty(): float
    {
        return $this->inputQty * $this->standardConversionPerUnit;
    }

    public function varianceQty(): float
    {
        return $this->actualOutputQty - $this->expectedOutputQty();
    }

    /**
     * @return Collection<int, WorkInProcess>
     */
    public function recentProcesses(): Collection
    {
        return WorkInProcess::query()
            ->with(['inputItem.defaultUnit:id,code', 'outputItem.defaultUnit:id,code', 'inputUnit', 'outputUnit', 'warehouse'])
            ->where('process_type', $this->processType)
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

    /**
     * @return array<int, string>
     */
    protected function inputStageCodes(): array
    {
        return [
            ItemStageCode::Srm->value,
            ItemStageCode::RawClean->value,
            ItemStageCode::Wip->value,
        ];
    }
}
