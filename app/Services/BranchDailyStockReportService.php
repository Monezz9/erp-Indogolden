<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ItemStageCode;
use App\Models\Item;
use App\Models\ItemStage;
use App\Models\StockBalance;
use App\Models\StockMovementItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BranchDailyStockReportService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function daily(int $branchId, Carbon|string|null $date = null): Collection
    {
        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date ?? now());
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $allowedStageIds = ItemStage::query()
            ->whereIn('code', [ItemStageCode::BranchStock->value, ItemStageCode::Mro->value])
            ->pluck('id')
            ->all();

        if ($allowedStageIds === []) {
            return collect();
        }

        $itemIds = StockMovementItem::query()
            ->where(function ($query) use ($branchId, $allowedStageIds) {
                $query
                    ->where(function ($incomingQuery) use ($branchId, $allowedStageIds) {
                        $incomingQuery
                            ->where('direction', 'in')
                            ->where('to_branch_id', $branchId)
                            ->whereIn('to_stage_id', $allowedStageIds);
                    })
                    ->orWhere(function ($outgoingQuery) use ($branchId, $allowedStageIds) {
                        $outgoingQuery
                            ->where('direction', 'out')
                            ->where('from_branch_id', $branchId)
                            ->whereIn('from_stage_id', $allowedStageIds);
                    });
            })
            ->whereHas('movement', fn ($movementQuery) => $movementQuery->where('status', ApprovalStatus::Approved->value))
            ->distinct()
            ->pluck('item_id')
            ->all();

        if ($itemIds === []) {
            return collect();
        }

        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->with('defaultUnit')
            ->orderBy('name')
            ->get();

        return $items->map(function (Item $item) use ($allowedStageIds, $branchId, $start, $end): array {
            $openingIn = $this->sumMovement(
                itemId: $item->id,
                direction: 'in',
                branchColumn: 'to_branch_id',
                stageColumn: 'to_stage_id',
                branchId: $branchId,
                stageIds: $allowedStageIds,
                to: $start,
            );

            $openingOut = $this->sumMovement(
                itemId: $item->id,
                direction: 'out',
                branchColumn: 'from_branch_id',
                stageColumn: 'from_stage_id',
                branchId: $branchId,
                stageIds: $allowedStageIds,
                to: $start,
            );

            $incomingToday = $this->sumMovement(
                itemId: $item->id,
                direction: 'in',
                branchColumn: 'to_branch_id',
                stageColumn: 'to_stage_id',
                branchId: $branchId,
                stageIds: $allowedStageIds,
                from: $start,
                to: $end,
            );

            $outgoingToday = $this->sumMovement(
                itemId: $item->id,
                direction: 'out',
                branchColumn: 'from_branch_id',
                stageColumn: 'from_stage_id',
                branchId: $branchId,
                stageIds: $allowedStageIds,
                from: $start,
                to: $end,
            );

            $opening = $openingIn - $openingOut;
            $closing = $opening + $incomingToday - $outgoingToday;

            $closingValue = (float) StockBalance::query()
                ->where('item_id', $item->id)
                ->where('branch_id', $branchId)
                ->whereIn('stage_id', $allowedStageIds)
                ->sum('total_value');

            return [
                'item_id' => $item->id,
                'sku' => $item->sku,
                'item_name' => $item->name,
                'unit' => $item->defaultUnit?->code,
                'opening_qty' => round($opening, 4),
                'incoming_qty' => round($incomingToday, 4),
                'outgoing_qty' => round($outgoingToday, 4),
                'closing_qty' => round($closing, 4),
                'closing_value' => round($closingValue, 2),
            ];
        });
    }

    protected function sumMovement(
        int $itemId,
        string $direction,
        string $branchColumn,
        string $stageColumn,
        int $branchId,
        array $stageIds,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): float {
        return (float) StockMovementItem::query()
            ->where('item_id', $itemId)
            ->where('direction', $direction)
            ->where($branchColumn, $branchId)
            ->whereIn($stageColumn, $stageIds)
            ->whereHas('movement', function ($movementQuery) use ($from, $to) {
                $movementQuery->where('status', ApprovalStatus::Approved->value);

                if ($from && $to) {
                    $movementQuery->whereBetween('movement_date', [$from, $to]);

                    return;
                }

                if ($to) {
                    $movementQuery->where('movement_date', '<', $to);
                }
            })
            ->sum('qty');
    }
}
