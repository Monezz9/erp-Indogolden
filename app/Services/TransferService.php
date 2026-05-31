<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\MovementType;
use App\Enums\TransferStatus;
use App\Models\Item;
use App\Models\ItemStage;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransferService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected ActivityLogService $activityLogService,
    ) {
    }

    public function submit(Transfer $transfer): Transfer
    {
        if ($transfer->status !== TransferStatus::Draft) {
            throw new InvalidArgumentException('Transfer hanya bisa disubmit dari draft.');
        }

        $transfer->update(['status' => TransferStatus::Submitted]);

        return $transfer;
    }

    public function approve(Transfer $transfer, User $approver): Transfer
    {
        if ($transfer->status !== TransferStatus::Submitted) {
            throw new InvalidArgumentException('Transfer yang dapat diapprove harus submitted.');
        }

        $transfer->update([
            'status' => TransferStatus::Approved,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return $transfer;
    }

    public function reject(Transfer $transfer, User $approver, ?string $notes = null): Transfer
    {
        if ($transfer->status !== TransferStatus::Submitted) {
            throw new InvalidArgumentException('Transfer yang dapat ditolak harus submitted.');
        }

        $transfer->update([
            'status' => TransferStatus::Rejected,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'notes' => trim((string) $transfer->notes.' '.$notes),
        ]);

        return $transfer;
    }

    public function ship(Transfer $transfer, User $shipper): Transfer
    {
        if (! in_array($transfer->status, [TransferStatus::Approved, TransferStatus::Submitted], true)) {
            throw new InvalidArgumentException('Transfer tidak dapat dikirim dari status ini.');
        }

        return DB::transaction(function () use ($transfer, $shipper) {
            $transfer->loadMissing('items.item');

            $movement = $this->stockMovementService->createDraft(
                movementData: [
                    'movement_number' => 'SM-'.now()->format('YmdHisv'),
                    'movement_date' => now(),
                    'movement_type' => $transfer->to_branch_id ? MovementType::BranchTransfer->value : MovementType::WarehouseTransfer->value,
                    'status' => ApprovalStatus::Draft,
                    'from_warehouse_id' => $transfer->from_warehouse_id,
                    'to_warehouse_id' => $transfer->to_warehouse_id,
                    'from_branch_id' => $transfer->from_branch_id,
                    'to_branch_id' => $transfer->to_branch_id,
                    'notes' => 'Transfer shipment '.$transfer->transfer_number,
                    'created_by' => $shipper->id,
                    'reference_type' => $transfer::class,
                    'reference_id' => $transfer->id,
                ],
                items: $transfer->items->map(fn ($item) => [
                    'item_id' => $item->item_id,
                    'unit_id' => $item->unit_id,
                    'direction' => 'out',
                    'qty' => $item->approved_qty > 0 ? $item->approved_qty : $item->requested_qty,
                    'unit_cost' => $item->unit_cost,
                    'from_stage_id' => $item->item->default_stage_id,
                    'from_warehouse_id' => $transfer->from_warehouse_id,
                    'from_branch_id' => $transfer->from_branch_id,
                ])->all(),
            );

            $this->stockMovementService->submit($movement);
            $this->stockMovementService->approve($movement, $shipper);

            $transfer->update([
                'status' => TransferStatus::Shipped,
                'shipped_by' => $shipper->id,
                'shipped_at' => now(),
            ]);

            return $transfer->fresh('items');
        });
    }

    public function receive(Transfer $transfer, User $receiver): Transfer
    {
        if ($transfer->status !== TransferStatus::Shipped) {
            throw new InvalidArgumentException('Transfer harus sudah shipped sebelum receive.');
        }

        return DB::transaction(function () use ($transfer, $receiver) {
            $transfer->loadMissing('items.item');

            $movement = $this->stockMovementService->createDraft(
                movementData: [
                    'movement_number' => 'SM-'.now()->format('YmdHisv'),
                    'movement_date' => now(),
                    'movement_type' => MovementType::BranchReceive->value,
                    'status' => ApprovalStatus::Draft,
                    'from_warehouse_id' => $transfer->from_warehouse_id,
                    'to_warehouse_id' => $transfer->to_warehouse_id,
                    'from_branch_id' => $transfer->from_branch_id,
                    'to_branch_id' => $transfer->to_branch_id,
                    'notes' => 'Transfer receive '.$transfer->transfer_number,
                    'created_by' => $receiver->id,
                    'reference_type' => $transfer::class,
                    'reference_id' => $transfer->id,
                ],
                items: $transfer->items->map(fn ($item) => [
                    'item_id' => $item->item_id,
                    'unit_id' => $item->unit_id,
                    'direction' => 'in',
                    'qty' => $item->shipped_qty > 0 ? $item->shipped_qty : ($item->approved_qty > 0 ? $item->approved_qty : $item->requested_qty),
                    'unit_cost' => $item->unit_cost,
                    'to_stage_id' => $transfer->to_branch_id ? $this->branchStageIdForItem($item->item) : $item->item->default_stage_id,
                    'to_warehouse_id' => $transfer->to_warehouse_id,
                    'to_branch_id' => $transfer->to_branch_id,
                ])->all(),
            );

            $this->stockMovementService->submit($movement);
            $this->stockMovementService->approve($movement, $receiver);

            $transfer->update([
                'status' => TransferStatus::Received,
                'received_by' => $receiver->id,
                'received_at' => now(),
            ]);

            $this->activityLogService->log(
                module: 'transfer',
                action: 'receive_transfer',
                subject: $transfer,
                actor: $receiver,
                after: ['status' => $transfer->status->value],
            );

            return $transfer->fresh('items');
        });
    }

    protected function branchStageIdForItem(Item $item): ?int
    {
        if ($item->defaultStage?->code === 'mro') {
            return $item->default_stage_id;
        }

        return ItemStage::query()->where('code', 'branch_stock')->value('id') ?? $item->default_stage_id;
    }
}
