<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ItemStageCode;
use App\Enums\MovementType;
use App\Models\CleaningProcess;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CleaningProcessService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected ActivityLogService $activityLogService,
    ) {}

    public function start(array $data, User $actor): CleaningProcess
    {
        $inputQty = (float) ($data['input_qty'] ?? 0);
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($inputQty <= 0) {
            throw new InvalidArgumentException('Qty masuk pembersihan harus lebih besar dari 0.');
        }

        if ($notes === '') {
            throw ValidationException::withMessages([
                'notes' => 'Catatan grooming wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($data, $actor, $inputQty, $notes): CleaningProcess {
            $rawDirtyStageId = $this->stageId(ItemStageCode::RawDirty);
            $srmStageId = $this->stageId(ItemStageCode::Srm);
            $item = Item::query()->with('category')->findOrFail((int) $data['item_id']);

            if (! $this->isRawMaterialItem($item)) {
                throw ValidationException::withMessages([
                    'item_id' => 'Hanya barang kategori RM yang dapat diproses grooming.',
                ]);
            }

            $outputItem = isset($data['output_item_id'])
                ? Item::query()->with('category')->findOrFail((int) $data['output_item_id'])
                : $this->resolveOutputItem($item, $srmStageId);

            if (! $this->isSrmItem($outputItem)) {
                throw ValidationException::withMessages([
                    'output_item_id' => 'Output grooming harus barang kategori SRM.',
                ]);
            }

            $warehouseId = (int) $data['warehouse_id'];

            $balance = StockBalance::query()
                ->where('item_id', $item->id)
                ->where('stage_id', $rawDirtyStageId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $balance || (float) $balance->qty_on_hand < $inputQty) {
                throw new InvalidArgumentException(sprintf(
                    'Stok mentah kotor tidak cukup. Tersedia %s, diminta %s.',
                    $balance ? (float) $balance->qty_on_hand : 0,
                    $inputQty,
                ));
            }

            $inputUnitCost = (float) $balance->avg_cost;
            $totalInputCost = $inputQty * $inputUnitCost;

            $process = CleaningProcess::query()->create([
                'process_number' => $data['process_number'] ?? $this->makeNumber(),
                'process_date' => $data['process_date'] ?? now()->toDateString(),
                'warehouse_id' => $warehouseId,
                'item_id' => $item->id,
                'output_item_id' => $outputItem->id,
                'unit_id' => (int) ($data['unit_id'] ?? $outputItem->default_unit_id),
                'input_qty' => $inputQty,
                'output_qty' => 0,
                'shrinkage_qty' => 0,
                'shrinkage_percent' => 0,
                'input_unit_cost' => $inputUnitCost,
                'output_unit_cost' => 0,
                'total_input_cost' => $totalInputCost,
                'status' => 'in_progress',
                'notes' => $notes,
                'created_by' => $actor->id,
                'posted_at' => null,
            ]);

            $outMovement = $this->stockMovementService->createDraft([
                'movement_number' => 'SM-'.now()->format('YmdHisv'),
                'movement_date' => now(),
                'movement_type' => MovementType::CleaningConversion->value,
                'status' => ApprovalStatus::Draft,
                'from_warehouse_id' => $warehouseId,
                'notes' => 'Pembersihan bahan '.$process->process_number,
                'created_by' => $actor->id,
                'reference_type' => $process::class,
                'reference_id' => $process->id,
            ], [[
                'item_id' => $item->id,
                'unit_id' => $process->unit_id,
                'direction' => 'out',
                'qty' => $inputQty,
                'unit_cost' => $inputUnitCost,
                'from_stage_id' => $rawDirtyStageId,
                'from_warehouse_id' => $warehouseId,
            ]]);

            $this->stockMovementService->submit($outMovement);
            $this->stockMovementService->approve($outMovement, $actor);

            $this->activityLogService->log(
                module: 'production',
                action: 'start_cleaning_process',
                subject: $process,
                actor: $actor,
                after: $process->toArray(),
            );

            return $process->fresh(['item', 'outputItem', 'warehouse', 'unit']);
        });
    }

    public function complete(CleaningProcess $process, array $data, User $actor): CleaningProcess
    {
        $outputQty = (float) ($data['output_qty'] ?? 0);

        if ($process->status !== 'in_progress') {
            throw new InvalidArgumentException('Hanya grooming in progress yang dapat diselesaikan.');
        }

        if ($outputQty <= 0) {
            throw new InvalidArgumentException('Qty hasil bersih harus lebih besar dari 0.');
        }

        if ($outputQty > (float) $process->input_qty) {
            throw new InvalidArgumentException('Qty hasil bersih tidak boleh lebih besar dari qty masuk.');
        }

        return DB::transaction(function () use ($process, $data, $actor, $outputQty): CleaningProcess {
            $process = CleaningProcess::query()
                ->with(['item.category', 'outputItem.category'])
                ->lockForUpdate()
                ->findOrFail($process->id);

            if ($process->status !== 'in_progress') {
                throw new InvalidArgumentException('Hanya grooming in progress yang dapat diselesaikan.');
            }

            $srmStageId = $this->stageId(ItemStageCode::Srm);
            $outputItem = isset($data['output_item_id'])
                ? Item::query()->with('category')->findOrFail((int) $data['output_item_id'])
                : $process->outputItem;

            if (! $outputItem || ! $this->isSrmItem($outputItem)) {
                throw ValidationException::withMessages([
                    'output_item_id' => 'Output grooming harus barang kategori SRM.',
                ]);
            }

            $inputQty = (float) $process->input_qty;
            $inputUnitCost = (float) $process->input_unit_cost;
            $totalInputCost = (float) $process->total_input_cost;
            $outputUnitCost = $totalInputCost / $outputQty;
            $shrinkageQty = $inputQty - $outputQty;
            $shrinkagePercent = ($shrinkageQty / $inputQty) * 100;
            $warehouseId = (int) $process->warehouse_id;

            $inMovement = $this->stockMovementService->createDraft([
                'movement_number' => 'SM-'.now()->addSecond()->format('YmdHisv'),
                'movement_date' => now(),
                'movement_type' => MovementType::CleaningConversion->value,
                'status' => ApprovalStatus::Draft,
                'to_warehouse_id' => $warehouseId,
                'notes' => 'Hasil pembersihan bahan '.$process->process_number,
                'created_by' => $actor->id,
                'reference_type' => $process::class,
                'reference_id' => $process->id,
            ], [[
                'item_id' => $outputItem->id,
                'unit_id' => $process->unit_id,
                'direction' => 'in',
                'qty' => $outputQty,
                'unit_cost' => $outputUnitCost,
                'to_stage_id' => $srmStageId,
                'to_warehouse_id' => $warehouseId,
            ]]);

            $this->stockMovementService->submit($inMovement);
            $this->stockMovementService->approve($inMovement, $actor);

            if ($shrinkageQty > 0) {
                $rawDirtyStageId = $this->stageId(ItemStageCode::RawDirty);
                $lossMovement = $this->stockMovementService->createDraft([
                    'movement_number' => 'SM-'.now()->addSeconds(2)->format('YmdHisv'),
                    'movement_date' => now(),
                    'movement_type' => MovementType::WasteShrinkage->value,
                    'status' => ApprovalStatus::Draft,
                    'from_warehouse_id' => $warehouseId,
                    'notes' => 'Susut grooming '.$process->process_number,
                    'created_by' => $actor->id,
                    'reference_type' => $process::class,
                    'reference_id' => $process->id,
                ], [[
                    'item_id' => $process->item_id,
                    'unit_id' => $process->unit_id,
                    'direction' => 'loss',
                    'qty' => $shrinkageQty,
                    'unit_cost' => $inputUnitCost,
                    'from_stage_id' => $rawDirtyStageId,
                    'from_warehouse_id' => $warehouseId,
                    'notes' => 'Susut grooming '.$process->process_number,
                ]]);

                $this->stockMovementService->submit($lossMovement);
                $this->stockMovementService->approve($lossMovement, $actor);
            }

            $process->update([
                'output_item_id' => $outputItem->id,
                'output_qty' => $outputQty,
                'shrinkage_qty' => $shrinkageQty,
                'shrinkage_percent' => $shrinkagePercent,
                'output_unit_cost' => $outputUnitCost,
                'status' => 'completed',
                'posted_at' => now(),
            ]);

            $this->activityLogService->log(
                module: 'production',
                action: 'complete_cleaning_process',
                subject: $process,
                actor: $actor,
                after: $process->toArray(),
            );

            return $process->fresh(['item', 'outputItem', 'warehouse', 'unit']);
        });
    }

    public function post(array $data, User $actor): CleaningProcess
    {
        $process = $this->start($data, $actor);

        return $this->complete($process, $data, $actor);
    }

    protected function stageId(ItemStageCode $code): int
    {
        return (int) ItemStage::query()->where('code', $code->value)->value('id');
    }

    protected function makeNumber(): string
    {
        $prefix = 'CLN-'.now()->format('Ymd');
        $last = CleaningProcess::query()
            ->where('process_number', 'like', $prefix.'-%')
            ->latest('id')
            ->value('process_number');

        $next = is_string($last) ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    protected function resolveOutputItem(Item $inputItem, int $srmStageId): Item
    {
        $categoryId = ItemCategory::query()->where('slug', 'srm')->value('id');

        if (! $categoryId) {
            throw ValidationException::withMessages([
                'output_item_id' => 'Item SRM tujuan belum tersedia. Buat master barang SRM terlebih dahulu.',
            ]);
        }

        $existing = Item::query()
            ->where('default_stage_id', $srmStageId)
            ->where('item_category_id', $categoryId)
            ->where(function ($query) use ($inputItem): void {
                $baseSku = $this->baseMaterialSku((string) $inputItem->sku);

                $query->where('name', $inputItem->name)
                    ->orWhere('sku', 'SRM-'.$baseSku);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        throw ValidationException::withMessages([
            'output_item_id' => 'Item SRM tujuan belum tersedia. Buat master barang SRM terlebih dahulu.',
        ]);
    }

    protected function isRawMaterialItem(Item $item): bool
    {
        $category = $item->category;
        $name = str($category?->name ?? '')->lower()->squish()->toString();
        $slug = str($category?->slug ?? '')->lower()->squish()->toString();

        return $slug === 'raw-material'
            || $name === 'raw material'
            || $name === 'rm';
    }

    protected function isSrmItem(Item $item): bool
    {
        $category = $item->category;
        $name = str($category?->name ?? '')->lower()->squish()->toString();
        $slug = str($category?->slug ?? '')->lower()->squish()->toString();

        return $slug === 'srm'
            || $name === 'srm';
    }

    protected function baseMaterialSku(string $sku): string
    {
        return preg_replace('/^(RM|SRM|RC)-/i', '', $sku) ?: $sku;
    }
}
