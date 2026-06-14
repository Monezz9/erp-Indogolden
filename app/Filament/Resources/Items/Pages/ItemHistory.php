<?php

namespace App\Filament\Resources\Items\Pages;

use App\Enums\MovementType;
use App\Filament\Resources\Items\ItemResource;
use App\Models\Item;
use App\Models\StockMovementItem;
use App\Support\IndoNumber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ItemHistory extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ItemResource::class;

    protected string $view = 'filament.resources.items.pages.item-history';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $title = 'Histori Barang';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $movementType = 'all';

    public ?string $search = null;

    public ?int $selectedHistoryLineId = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Histori Barang - '.$this->getRecord()->name;
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_items')
                ->label('Kembali ke Barang')
                ->icon('heroicon-o-arrow-left')
                ->outlined()
                ->url(ItemResource::getUrl('index')),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function historyRows(): Collection
    {
        $runningBalance = 0.0;

        return $this->movementLines()
            ->map(function (StockMovementItem $line) use (&$runningBalance): array {
                $qty = (float) $line->qty;
                $inQty = $line->direction === 'in' ? $qty : 0.0;
                $outQty = $line->direction === 'out' ? $qty : 0.0;
                $runningBalance += $inQty - $outQty;
                $qtyPrefix = match ($line->direction) {
                    'in' => '+',
                    'loss' => 'Susut ',
                    default => '-',
                };
                $movementType = (string) $line->movement?->movement_type;
                $movementLabel = $line->direction === 'loss' && $movementType === MovementType::WasteShrinkage->value
                    ? 'Susut Grooming'
                    : $this->movementTypeLabel($movementType);

                return [
                    'id' => $line->id,
                    'date' => $line->movement?->movement_date,
                    'movement_type' => $movementType,
                    'movement_label' => $movementLabel,
                    'activity_tone' => $this->activityTone($movementType, $line->direction),
                    'qty_label' => $qtyPrefix.IndoNumber::decimal($qty).' '.($line->unit?->code ?? $this->item()->defaultUnit?->code ?? ''),
                    'reference' => $this->referenceLabel($line),
                    'in_qty' => $inQty,
                    'out_qty' => $outQty,
                    'balance_after' => $runningBalance,
                    'unit_cost' => (float) $line->unit_cost,
                    'unit_code' => $line->unit?->code ?? $this->item()->defaultUnit?->code ?? '',
                    'notes' => $line->notes ?: $line->movement?->notes,
                    'user' => $line->movement?->creator?->name ?? '-',
                    'reference_details' => $this->referenceDetails($line),
                ];
            })
            ->filter(fn (array $row): bool => $this->passesFilters($row))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $rows = $this->historyRows();

        return [
            'current_stock' => (float) $this->item()->stockBalances()->sum('qty_on_hand'),
            'stock_unit' => $this->item()->defaultUnit?->code ?? '',
            'total_in' => (float) $rows->sum('in_qty'),
            'total_out' => (float) $rows->sum('out_qty'),
            'latest_hpp' => (float) ($this->item()->latest_weighted_avg_cost ?? 0),
            'latest_activity_at' => $rows->pluck('date')->filter()->sort()->last(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function movementTypeOptions(): array
    {
        return [
            'all' => 'Semua Aktivitas',
            'Pengadaan' => 'Pengadaan',
            'Grooming' => 'Grooming',
            'Susut Grooming' => 'Susut Grooming',
            'Selep' => 'Selep',
            'Produksi' => 'Produksi',
            'Premix' => 'Premix',
            'WIP Keringan' => 'WIP Keringan',
            'Vendor' => 'Vendor',
            'Request Cabang' => 'Request Cabang',
            'Pengiriman' => 'Pengiriman',
            'Pengiriman Cabang' => 'Pengiriman Cabang',
            'Receive Cabang' => 'Receive Cabang',
            'Retur' => 'Retur',
            'Adjustment' => 'Adjustment',
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timelineRows(): Collection
    {
        return $this->historyRows()
            ->reverse()
            ->take(10)
            ->values();
    }

    public function showHistoryDetail(int $lineId): void
    {
        $this->selectedHistoryLineId = $lineId;
    }

    public function closeHistoryDetail(): void
    {
        $this->selectedHistoryLineId = null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function selectedHistoryRow(): ?array
    {
        if (! $this->selectedHistoryLineId) {
            return null;
        }

        return $this->historyRows()
            ->firstWhere('id', $this->selectedHistoryLineId);
    }

    public function item(): Item
    {
        /** @var Item $item */
        $item = $this->getRecord()->loadMissing(['defaultUnit', 'category']);

        return $item;
    }

    /**
     * @return EloquentCollection<int, StockMovementItem>
     */
    protected function movementLines(): EloquentCollection
    {
        return StockMovementItem::query()
            ->with(['movement.creator', 'movement.reference', 'unit'])
            ->where('item_id', $this->item()->id)
            ->whereHas('movement')
            ->join('stock_movements', 'stock_movement_items.stock_movement_id', '=', 'stock_movements.id')
            ->orderBy('stock_movements.movement_date')
            ->orderBy('stock_movement_items.id')
            ->select('stock_movement_items.*')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function passesFilters(array $row): bool
    {
        $date = $row['date'] instanceof Carbon ? $row['date'] : null;

        if ($this->dateFrom && $date?->lt(Carbon::parse($this->dateFrom)->startOfDay())) {
            return false;
        }

        if ($this->dateTo && $date?->gt(Carbon::parse($this->dateTo)->endOfDay())) {
            return false;
        }

        if ($this->movementType !== 'all' && $row['movement_label'] !== $this->movementType) {
            return false;
        }

        $search = str($this->search ?? '')->lower()->trim()->toString();

        if ($search !== '') {
            $haystack = str(implode(' ', [
                $row['reference'] ?? '',
                $row['notes'] ?? '',
                $row['movement_label'] ?? '',
                $row['user'] ?? '',
            ]))->lower()->toString();

            if (! str_contains($haystack, $search)) {
                return false;
            }
        }

        return true;
    }

    protected function movementTypeLabel(string $type): string
    {
        return match ($type) {
            MovementType::InboundPurchase->value => 'Pengadaan',
            MovementType::CleaningConversion->value => 'Grooming',
            MovementType::WorkInProcess->value => 'WIP Keringan',
            MovementType::ProductionConsumption->value, MovementType::ProductionOutput->value => 'Produksi',
            MovementType::WarehouseTransfer->value, MovementType::BranchTransfer->value => 'Pengiriman Cabang',
            MovementType::BranchReceive->value => 'Receive Cabang',
            MovementType::BranchSale->value => 'Request Cabang',
            MovementType::StockAdjustment->value, MovementType::StockOpname->value => 'Adjustment',
            MovementType::WasteShrinkage->value => 'Retur',
            default => MovementType::options()[$type] ?? str($type)->replace('_', ' ')->title()->toString(),
        };
    }

    protected function activityTone(string $type, string $direction): string
    {
        if ($direction === 'loss') {
            return 'transform';
        }

        if (in_array($type, [MovementType::CleaningConversion->value, MovementType::WorkInProcess->value], true)) {
            return 'transform';
        }

        return $direction === 'in' ? 'in' : 'out';
    }

    protected function referenceLabel(StockMovementItem $line): string
    {
        $movement = $line->movement;
        $reference = $movement?->reference;

        if (! $movement) {
            return '-';
        }

        foreach (['receipt_number', 'po_number', 'movement_number', 'transfer_number', 'sale_number', 'request_number', 'production_number', 'code'] as $attribute) {
            if ($reference && filled($reference->{$attribute} ?? null)) {
                return (string) $reference->{$attribute};
            }
        }

        return $movement->movement_number;
    }

    /**
     * @return array<string, string>
     */
    protected function referenceDetails(StockMovementItem $line): array
    {
        $movement = $line->movement;
        $reference = $movement?->reference;
        $details = [];

        if (! $reference) {
            return $details;
        }

        foreach ([
            'supplier' => 'Supplier',
            'branch' => 'Cabang',
            'fromBranch' => 'Cabang Asal',
            'toBranch' => 'Cabang Tujuan',
            'warehouse' => 'Gudang',
            'fromWarehouse' => 'Gudang Asal',
            'toWarehouse' => 'Gudang Tujuan',
            'productionOrder' => 'Production Order',
            'purchaseOrder' => 'Pengadaan',
        ] as $relation => $label) {
            if (! method_exists($reference, $relation)) {
                continue;
            }

            $related = $reference->{$relation};

            if ($related && filled($related->name ?? $related->code ?? null)) {
                $details[$label] = (string) ($related->name ?? $related->code);
            }
        }

        return $details;
    }

    public function formatQty(float $qty, string $unitCode): string
    {
        if ($qty <= 0) {
            return '-';
        }

        return IndoNumber::decimal($qty).' '.$unitCode;
    }
}
