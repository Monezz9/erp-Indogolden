<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ItemStageCode;
use App\Enums\MovementType;
use App\Models\Item;
use App\Models\ItemStage;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\WorkInProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

class WorkInProcessService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected ActivityLogService $activityLogService,
    ) {}

    public function post(array $data, User $actor): WorkInProcess
    {
        $inputQty = (float) ($data['input_qty'] ?? 0);
        $standardConversion = (float) ($data['standard_conversion_per_unit'] ?? 0);
        $actualOutputQty = (float) ($data['actual_output_qty'] ?? 0);
        $overheadCost = (float) ($data['overhead_cost'] ?? 0);

        if ($inputQty <= 0) {
            throw new InvalidArgumentException('Qty SRM yang diproses harus lebih besar dari 0.');
        }

        if ($actualOutputQty <= 0) {
            throw new InvalidArgumentException('Hasil PCS harus lebih besar dari 0.');
        }

        if ($standardConversion < 0 || $overheadCost < 0) {
            throw new InvalidArgumentException('Konversi dan overhead tidak boleh minus.');
        }

        return DB::transaction(function () use ($data, $actor, $inputQty, $standardConversion, $actualOutputQty, $overheadCost): WorkInProcess {
            $inputStageIds = $this->stageIds([
                ItemStageCode::Srm,
            ]);
            $finishedGoodsStageId = $this->stageId(ItemStageCode::FinishedGoods);
            $inputItem = Item::query()->with(['category', 'defaultUnit'])->findOrFail((int) $data['input_item_id']);
            $outputItem = Item::query()->with('defaultUnit')->findOrFail((int) $data['output_item_id']);

            if (! $this->isSrmItem($inputItem)) {
                throw ValidationException::withMessages([
                    'input_item_id' => 'Input produksi harus barang kategori SRM.',
                ]);
            }
            $warehouseId = (int) $data['warehouse_id'];
            $preferredInputStageId = in_array((int) $inputItem->default_stage_id, $inputStageIds, true)
                ? (int) $inputItem->default_stage_id
                : (int) ($inputStageIds[0] ?? 0);

            $balance = StockBalance::query()
                ->where('item_id', $inputItem->id)
                ->whereIn('stage_id', $inputStageIds)
                ->where('warehouse_id', $warehouseId)
                ->where('qty_on_hand', '>=', $inputQty)
                ->when($preferredInputStageId > 0, fn ($query) => $query->orderByRaw('CASE WHEN stage_id = ? THEN 0 ELSE 1 END', [$preferredInputStageId]))
                ->orderByDesc('qty_on_hand')
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $availableQty = StockBalance::query()
                    ->where('item_id', $inputItem->id)
                    ->whereIn('stage_id', $inputStageIds)
                    ->where('warehouse_id', $warehouseId)
                    ->sum('qty_on_hand');

                throw new InvalidArgumentException(sprintf(
                    'Stok SRM tidak cukup. Tersedia %s, diminta %s.',
                    (float) $availableQty,
                    $inputQty,
                ));
            }

            $inputUnitCost = (float) $balance->avg_cost;
            $totalInputCost = $inputQty * $inputUnitCost;
            $totalOutputCost = $totalInputCost + $overheadCost;
            $outputUnitCost = $totalOutputCost / $actualOutputQty;
            $expectedOutputQty = $inputQty * $standardConversion;
            $varianceQty = $actualOutputQty - $expectedOutputQty;

            $process = WorkInProcess::query()->create([
                'process_number' => $data['process_number'] ?? $this->makeNumber(),
                'process_date' => $data['process_date'] ?? now()->toDateString(),
                'process_type' => $data['process_type'] ?? 'internal',
                'warehouse_id' => $warehouseId,
                'input_item_id' => $inputItem->id,
                'output_item_id' => $outputItem->id,
                'input_unit_id' => (int) ($data['input_unit_id'] ?? $inputItem->default_unit_id),
                'output_unit_id' => (int) ($data['output_unit_id'] ?? $outputItem->default_unit_id),
                'input_qty' => $inputQty,
                'standard_conversion_per_unit' => $standardConversion,
                'expected_output_qty' => $expectedOutputQty,
                'actual_output_qty' => $actualOutputQty,
                'variance_qty' => $varianceQty,
                'overhead_cost' => $overheadCost,
                'input_unit_cost' => $inputUnitCost,
                'total_input_cost' => $totalInputCost,
                'output_unit_cost' => $outputUnitCost,
                'total_output_cost' => $totalOutputCost,
                'vendor_name' => $data['vendor_name'] ?? null,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'posted_at' => now(),
            ]);

            $outMovement = $this->stockMovementService->createDraft([
                'movement_number' => 'SM-'.now()->format('YmdHisv'),
                'movement_date' => now(),
                'movement_type' => MovementType::WorkInProcess->value,
                'status' => ApprovalStatus::Draft,
                'from_warehouse_id' => $warehouseId,
                'notes' => 'WIP keluar SRM '.$process->process_number,
                'created_by' => $actor->id,
                'reference_type' => $process::class,
                'reference_id' => $process->id,
            ], [[
                'item_id' => $inputItem->id,
                'unit_id' => $process->input_unit_id,
                'direction' => 'out',
                'qty' => $inputQty,
                'unit_cost' => $inputUnitCost,
                'from_stage_id' => (int) $balance->stage_id,
                'from_warehouse_id' => $warehouseId,
            ]]);

            $this->stockMovementService->submit($outMovement);
            $this->stockMovementService->approve($outMovement, $actor);

            $inMovement = $this->stockMovementService->createDraft([
                'movement_number' => 'SM-'.now()->addSecond()->format('YmdHisv'),
                'movement_date' => now(),
                'movement_type' => MovementType::WorkInProcess->value,
                'status' => ApprovalStatus::Draft,
                'to_warehouse_id' => $warehouseId,
                'notes' => 'WIP hasil FG '.$process->process_number,
                'created_by' => $actor->id,
                'reference_type' => $process::class,
                'reference_id' => $process->id,
            ], [[
                'item_id' => $outputItem->id,
                'unit_id' => $process->output_unit_id,
                'direction' => 'in',
                'qty' => $actualOutputQty,
                'unit_cost' => $outputUnitCost,
                'to_stage_id' => $finishedGoodsStageId,
                'to_warehouse_id' => $warehouseId,
            ]]);

            $this->stockMovementService->submit($inMovement);
            $this->stockMovementService->approve($inMovement, $actor);

            $this->activityLogService->log(
                module: 'production',
                action: 'post_work_in_process',
                subject: $process,
                actor: $actor,
                after: $process->toArray(),
            );

            return $process->fresh(['inputItem', 'outputItem', 'warehouse', 'inputUnit', 'outputUnit']);
        });
    }

    protected function stageId(ItemStageCode $code): int
    {
        return (int) ItemStage::query()->where('code', $code->value)->value('id');
    }

    /**
     * @param  array<int, ItemStageCode>  $codes
     * @return array<int, int>
     */
    protected function stageIds(array $codes): array
    {
        return ItemStage::query()
            ->whereIn('code', array_map(fn (ItemStageCode $code): string => $code->value, $codes))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    protected function makeNumber(): string
    {
        $prefix = 'WIP-'.now()->format('Ymd');
        $last = WorkInProcess::query()
            ->where('process_number', 'like', $prefix.'-%')
            ->latest('id')
            ->value('process_number');

        $next = is_string($last) ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    protected function isSrmItem(Item $item): bool
    {
        $category = $item->category;
        $name = str($category?->name ?? '')->lower()->squish()->toString();
        $slug = str($category?->slug ?? '')->lower()->squish()->toString();

        return in_array($slug, ['srm', 'raw-clean', 'premix'], true)
            || in_array($name, ['srm', 'raw clean', 'premix'], true);
    }
}
