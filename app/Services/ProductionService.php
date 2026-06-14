<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\MovementType;
use App\Enums\ProductionOrderStatus;
use App\Models\ProductionOrder;
use App\Models\ProductionRecipe;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ProductionService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected StockBalanceService $stockBalanceService,
        protected ActivityLogService $activityLogService,
    ) {
    }

    public function createOrder(ProductionRecipe $recipe, float $targetQty, User $creator): ProductionOrder
    {
        if ($targetQty <= 0) {
            throw new InvalidArgumentException('Target qty harus lebih besar dari 0.');
        }

        $order = ProductionOrder::query()->create([
            'order_number' => 'PO-'.now()->format('YmdHisv'),
            'production_recipe_id' => $recipe->id,
            'status' => ProductionOrderStatus::Draft,
            'planned_date' => now()->toDateString(),
            'output_item_id' => $recipe->output_item_id,
            'output_unit_id' => $recipe->output_unit_id,
            'target_qty' => $targetQty,
            'warehouse_id' => null,
            'created_by' => $creator->id,
        ]);

        $recipe->loadMissing('ingredients.item.category');
        $this->ensureRecipeInputsAreSrm($recipe);

        foreach ($recipe->ingredients as $ingredient) {
            $ratio = $targetQty / (float) $recipe->output_qty;
            $plannedQty = (float) $ingredient->qty * $ratio;

            $order->inputs()->create([
                'item_id' => $ingredient->item_id,
                'unit_id' => $ingredient->unit_id,
                'stage_id' => $ingredient->stage_id,
                'planned_qty' => $plannedQty,
                'actual_qty' => $plannedQty,
            ]);
        }

        $order->outputs()->create([
            'item_id' => $recipe->output_item_id,
            'unit_id' => $recipe->output_unit_id,
            'qty' => $targetQty,
            'is_byproduct' => false,
        ]);

        return $order->fresh(['inputs', 'outputs']);
    }

    public function submitOrder(ProductionOrder $order, User $actor): ProductionOrder
    {
        if ($order->status !== ProductionOrderStatus::Draft) {
            throw new InvalidArgumentException('Production order hanya bisa disubmit dari draft.');
        }

        $order->update(['status' => ProductionOrderStatus::Submitted]);

        $this->activityLogService->log(
            module: 'production',
            action: 'submit_order',
            subject: $order,
            actor: $actor,
            after: ['status' => $order->status->value],
        );

        return $order;
    }

    public function approveOrder(ProductionOrder $order, User $actor): ProductionOrder
    {
        if ($order->status !== ProductionOrderStatus::Submitted) {
            throw new InvalidArgumentException('Production order hanya bisa diapprove dari submitted.');
        }

        $order->update([
            'status' => ProductionOrderStatus::Approved,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        $this->activityLogService->log(
            module: 'production',
            action: 'approve_order',
            subject: $order,
            actor: $actor,
            after: ['status' => $order->status->value],
        );

        return $order;
    }

    public function completeOrder(ProductionOrder $order, User $actor, ?int $warehouseId = null): ProductionOrder
    {
        if ($order->status === ProductionOrderStatus::Completed) {
            throw new InvalidArgumentException('Production order sudah completed.');
        }

        if (! in_array($order->status, [ProductionOrderStatus::Submitted, ProductionOrderStatus::Approved, ProductionOrderStatus::InProgress], true)) {
            throw new InvalidArgumentException('Production order harus submitted/approved sebelum complete.');
        }

        return DB::transaction(function () use ($order, $actor, $warehouseId) {
            $order->loadMissing('inputs.item.defaultStage', 'outputs.item.defaultStage');
            $this->ensureOrderInputsAreSrm($order);

            $this->refreshInputCosts($order, $warehouseId);

            $consumptionMovement = $this->stockMovementService->createDraft(
                movementData: [
                    'movement_number' => 'SM-'.now()->format('YmdHisv'),
                    'movement_date' => now(),
                    'movement_type' => MovementType::ProductionConsumption->value,
                    'status' => ApprovalStatus::Draft,
                    'notes' => 'Production consumption for '.$order->order_number,
                    'created_by' => $actor->id,
                    'reference_type' => $order::class,
                    'reference_id' => $order->id,
                ],
                items: $order->inputs->map(fn ($input) => [
                    'item_id' => $input->item_id,
                    'unit_id' => $input->unit_id,
                    'direction' => 'out',
                    'qty' => $input->actual_qty,
                    'unit_cost' => $input->unit_cost,
                    'from_stage_id' => $input->stage_id ?: $input->item->default_stage_id,
                    'from_warehouse_id' => $warehouseId ?? $input->warehouse_id,
                ])->all(),
            );

            $this->stockMovementService->submit($consumptionMovement);
            $this->stockMovementService->approve($consumptionMovement, $actor);

            $outputUnitCost = $order->inputs->sum('total_cost') / max(1, (float) $order->outputs->sum('qty'));
            $order->outputs->each(function ($output) use ($outputUnitCost): void {
                $unitCost = (float) $output->unit_cost > 0 ? (float) $output->unit_cost : $outputUnitCost;

                $output->update([
                    'unit_cost' => $unitCost,
                    'total_cost' => (float) $output->qty * $unitCost,
                ]);
            });

            $order->load('outputs');

            $outputMovement = $this->stockMovementService->createDraft(
                movementData: [
                    'movement_number' => 'SM-'.now()->addSecond()->format('YmdHisv'),
                    'movement_date' => now(),
                    'movement_type' => MovementType::ProductionOutput->value,
                    'status' => ApprovalStatus::Draft,
                    'notes' => 'Production output for '.$order->order_number,
                    'created_by' => $actor->id,
                    'reference_type' => $order::class,
                    'reference_id' => $order->id,
                ],
                items: $order->outputs->map(fn ($output) => [
                    'item_id' => $output->item_id,
                    'unit_id' => $output->unit_id,
                    'direction' => 'in',
                    'qty' => $output->qty,
                    'unit_cost' => $output->unit_cost > 0 ? $output->unit_cost : $outputUnitCost,
                    'to_stage_id' => $output->stage_id ?: $output->item->default_stage_id,
                    'to_warehouse_id' => $warehouseId ?? $output->warehouse_id,
                ])->all(),
            );

            $this->stockMovementService->submit($outputMovement);
            $this->stockMovementService->approve($outputMovement, $actor);

            $order->update([
                'status' => ProductionOrderStatus::Completed,
                'completed_at' => now(),
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'warehouse_id' => $warehouseId ?? $order->warehouse_id,
                'actual_qty' => $order->outputs->sum('qty'),
                'total_input_cost' => $order->inputs->sum('total_cost'),
                'total_output_cost' => $order->outputs->sum('total_cost'),
            ]);

            $this->activityLogService->log(
                module: 'production',
                action: 'complete_order',
                subject: $order,
                actor: $actor,
                after: ['status' => $order->status->value],
            );

            return $order->fresh(['inputs', 'outputs']);
        });
    }

    protected function refreshInputCosts(ProductionOrder $order, ?int $warehouseId = null): void
    {
        $order->inputs->each(function ($input) use ($warehouseId): void {
            $stageId = $input->stage_id ?: $input->item->default_stage_id;
            $resolvedWarehouseId = $warehouseId ?? $input->warehouse_id;

            $balance = $this->stockBalanceService->getOrCreate(
                itemId: $input->item_id,
                stageId: (int) $stageId,
                warehouseId: $resolvedWarehouseId,
                branchId: null,
                stockBatchId: null,
            );

            $unitCost = (float) ($balance?->avg_cost ?? 0);

            if ($unitCost <= 0) {
                $unitCost = (float) ($input->item->latest_weighted_avg_cost ?: $input->item->purchase_price);
            }

            $input->update([
                'unit_cost' => $unitCost,
                'total_cost' => (float) $input->actual_qty * $unitCost,
            ]);
        });

        $order->load('inputs');
    }

    protected function ensureRecipeInputsAreSrm(ProductionRecipe $recipe): void
    {
        foreach ($recipe->ingredients as $ingredient) {
            if ($this->isSrmItem($ingredient->item)) {
                continue;
            }

            throw ValidationException::withMessages([
                'ingredients' => 'Semua input produksi harus barang kategori SRM.',
            ]);
        }
    }

    protected function ensureOrderInputsAreSrm(ProductionOrder $order): void
    {
        $order->loadMissing('inputs.item.category');

        foreach ($order->inputs as $input) {
            if ($this->isSrmItem($input->item)) {
                continue;
            }

            throw ValidationException::withMessages([
                'inputs' => 'Semua input produksi harus barang kategori SRM.',
            ]);
        }
    }

    protected function isSrmItem($item): bool
    {
        $category = $item?->category;
        $name = str($category?->name ?? '')->lower()->squish()->toString();
        $slug = str($category?->slug ?? '')->lower()->squish()->toString();

        return in_array($slug, ['srm', 'raw-clean', 'premix'], true)
            || in_array($name, ['srm', 'raw clean', 'premix'], true);
    }
}
