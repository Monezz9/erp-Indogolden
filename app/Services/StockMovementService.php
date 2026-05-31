<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockMovementService
{
    public function __construct(
        protected StockBalanceService $stockBalanceService,
        protected ActivityLogService $activityLogService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $movementData
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createDraft(array $movementData, array $items): StockMovement
    {
        return DB::transaction(function () use ($movementData, $items) {
            $movement = StockMovement::query()->create([
                ...$movementData,
                'status' => ApprovalStatus::Draft,
            ]);

            $totalCost = 0;

            foreach ($items as $itemData) {
                $lineTotal = (float) $itemData['qty'] * (float) Arr::get($itemData, 'unit_cost', 0);

                $movement->items()->create([
                    ...$itemData,
                    'total_cost' => $lineTotal,
                ]);

                $totalCost += $lineTotal;
            }

            $movement->update(['total_cost' => $totalCost]);

            $this->activityLogService->log(
                module: 'inventory',
                action: 'create_movement',
                subject: $movement,
                after: $movement->toArray(),
            );

            return $movement->fresh(['items']);
        });
    }

    public function submit(StockMovement $movement): StockMovement
    {
        if ($movement->status !== ApprovalStatus::Draft) {
            throw new InvalidArgumentException('Hanya movement draft yang bisa disubmit.');
        }

        $movement->update(['status' => ApprovalStatus::Submitted]);

        $this->activityLogService->log(
            module: 'inventory',
            action: 'submit_movement',
            subject: $movement,
            after: ['status' => $movement->status->value],
        );

        return $movement;
    }

    public function approve(StockMovement $movement, User $approver): StockMovement
    {
        if (! in_array($movement->status, [ApprovalStatus::Submitted, ApprovalStatus::Draft], true)) {
            throw new InvalidArgumentException('Movement tidak dapat di-approve pada status ini.');
        }

        return DB::transaction(function () use ($movement, $approver) {
            $movement->loadMissing('items');

            foreach ($movement->items as $line) {
                $this->validateLine($line);

                if ($line->direction === 'out') {
                    $this->stockBalanceService->applyOutbound($line);
                }

                if ($line->direction === 'in') {
                    $this->stockBalanceService->applyInbound($line);
                }
            }

            $movement->update([
                'status' => ApprovalStatus::Approved,
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $this->activityLogService->log(
                module: 'inventory',
                action: 'approve_movement',
                subject: $movement,
                after: ['status' => $movement->status->value],
                actor: $approver,
            );

            return $movement->fresh(['items']);
        });
    }

    public function reject(StockMovement $movement, User $approver, ?string $notes = null): StockMovement
    {
        if ($movement->status !== ApprovalStatus::Submitted) {
            throw new InvalidArgumentException('Movement yang dapat ditolak harus berstatus submitted.');
        }

        $movement->update([
            'status' => ApprovalStatus::Rejected,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'notes' => trim((string) $movement->notes.' '.$notes),
        ]);

        $this->activityLogService->log(
            module: 'inventory',
            action: 'reject_movement',
            subject: $movement,
            actor: $approver,
            after: ['status' => $movement->status->value],
        );

        return $movement;
    }

    protected function validateLine(StockMovementItem $line): void
    {
        if ($line->qty <= 0) {
            throw new InvalidArgumentException('Qty movement harus lebih besar dari 0.');
        }

        if ($line->direction === 'out' && empty($line->from_stage_id)) {
            throw new InvalidArgumentException('Line OUT harus memiliki from_stage_id.');
        }

        if ($line->direction === 'in' && empty($line->to_stage_id)) {
            throw new InvalidArgumentException('Line IN harus memiliki to_stage_id.');
        }
    }

    public function guardSufficientStock(StockMovement $movement): void
    {
        foreach ($movement->items as $line) {
            if ($line->direction !== 'out') {
                continue;
            }

            $balance = $this->stockBalanceService->getOrCreate(
                $line->item_id,
                (int) $line->from_stage_id,
                $line->from_warehouse_id,
                $line->from_branch_id,
                $line->stock_batch_id,
            );

            if ((float) $balance->qty_on_hand < (float) $line->qty) {
                throw new InsufficientStockException('Stok tidak cukup untuk menyelesaikan movement ini.');
            }
        }
    }
}
