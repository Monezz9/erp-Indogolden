<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockMovementItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockBalanceService
{
    public function buildBalanceKey(
        int $itemId,
        int $stageId,
        ?int $warehouseId,
        ?int $branchId,
        ?int $stockBatchId,
    ): string {
        return implode(':', [
            $itemId,
            $stageId,
            $warehouseId ?? 0,
            $branchId ?? 0,
            $stockBatchId ?? 0,
        ]);
    }

    public function getOrCreate(
        int $itemId,
        int $stageId,
        ?int $warehouseId,
        ?int $branchId,
        ?int $stockBatchId,
    ): StockBalance {
        $resolvedBranchId = $this->resolveBranchId($branchId, $warehouseId);

        $balanceKey = $this->buildBalanceKey($itemId, $stageId, $warehouseId, $resolvedBranchId, $stockBatchId);

        return StockBalance::query()->firstOrCreate(
            ['balance_key' => $balanceKey],
            [
                'item_id' => $itemId,
                'stage_id' => $stageId,
                'warehouse_id' => $warehouseId,
                'branch_id' => $resolvedBranchId,
                'stock_batch_id' => $stockBatchId,
                'qty_on_hand' => 0,
                'avg_cost' => 0,
                'total_value' => 0,
            ],
        );
    }

    protected function resolveBranchId(?int $branchId, ?int $warehouseId): ?int
    {
        if ($branchId !== null) {
            return $branchId;
        }

        if ($warehouseId === null) {
            return null;
        }

        return Warehouse::query()->whereKey($warehouseId)->value('branch_id');
    }

    public function applyInbound(StockMovementItem $movementItem): void
    {
        $balance = $this->getOrCreate(
            itemId: $movementItem->item_id,
            stageId: (int) $movementItem->to_stage_id,
            warehouseId: $movementItem->to_warehouse_id,
            branchId: $movementItem->to_branch_id,
            stockBatchId: $movementItem->stock_batch_id,
        );

        $incomingQty = (float) $movementItem->qty;
        $incomingCost = (float) $movementItem->unit_cost;

        $existingQty = (float) $balance->qty_on_hand;
        $existingValue = (float) $balance->total_value;
        $incomingValue = $incomingQty * $incomingCost;

        $newQty = $existingQty + $incomingQty;
        $newValue = $existingValue + $incomingValue;
        $newAvg = $newQty > 0 ? ($newValue / $newQty) : 0;

        $balance->update([
            'qty_on_hand' => $newQty,
            'avg_cost' => $newAvg,
            'total_value' => $newValue,
            'last_movement_item_id' => $movementItem->id,
            'last_updated_at' => now(),
        ]);

        Item::query()->whereKey($movementItem->item_id)->update([
            'latest_weighted_avg_cost' => $newAvg,
        ]);
    }

    public function applyOutbound(StockMovementItem $movementItem): void
    {
        $balance = $this->getOrCreate(
            itemId: $movementItem->item_id,
            stageId: (int) $movementItem->from_stage_id,
            warehouseId: $movementItem->from_warehouse_id,
            branchId: $movementItem->from_branch_id,
            stockBatchId: $movementItem->stock_batch_id,
        );

        $existingQty = (float) $balance->qty_on_hand;
        $existingAvg = (float) $balance->avg_cost;
        $outQty = (float) $movementItem->qty;

        if ($existingQty < $outQty) {
            throw new InsufficientStockException(sprintf(
                'Stok tidak cukup untuk item #%d. Tersedia %.4f, diminta %.4f.',
                $movementItem->item_id,
                $existingQty,
                $outQty,
            ));
        }

        $outValue = $outQty * $existingAvg;
        $newQty = $existingQty - $outQty;
        $newValue = max(0, ((float) $balance->total_value) - $outValue);

        $balance->update([
            'qty_on_hand' => $newQty,
            'total_value' => $newValue,
            'last_movement_item_id' => $movementItem->id,
            'last_updated_at' => now(),
        ]);
    }

    public function lockBalance(string $balanceKey): ?StockBalance
    {
        return StockBalance::query()
            ->where('balance_key', $balanceKey)
            ->lockForUpdate()
            ->first();
    }

    public function withTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
