<?php

namespace App\Services;

use App\Enums\BranchRequestItemStatus;
use App\Enums\BranchRequestStatus;
use App\Models\BranchRequest;
use App\Models\BranchRequestItem;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchRequestService
{
    public function prepareDraft(BranchRequest $request, User $actor): BranchRequest
    {
        $request->fill([
            'status' => BranchRequestStatus::Draft->value,
            'created_by' => $request->created_by ?? $actor->id,
            'branch_id' => $request->branch_id ?? $actor->branch_id,
            'request_date' => $request->request_date ?? now()->toDateString(),
            'delivery_date' => $request->delivery_date ?? now()->addDay()->toDateString(),
        ]);

        if (blank($request->request_number)) {
            $request->request_number = $this->makeNumber();
        }

        $request->save();

        return $request->refresh();
    }

    public function submit(BranchRequest $request, User $actor): BranchRequest
    {
        $this->assertCanTransition($request, [BranchRequestStatus::Draft], 'submit');

        if ($actor->isBranchLike() && $actor->branch_id !== $request->branch_id) {
            throw new \RuntimeException('User cabang hanya boleh submit request cabangnya sendiri.');
        }

        if ($request->items()->count() === 0) {
            throw new \RuntimeException('Tambahkan minimal 1 item request sebelum submit.');
        }

        return DB::transaction(function () use ($request, $actor): BranchRequest {
            $before = $request->toArray();

            $request->update([
                'status' => BranchRequestStatus::Submitted->value,
                'submitted_at' => now(),
            ]);

            $request->items()->where('item_status', BranchRequestItemStatus::Requested->value)->update([
                'item_status' => BranchRequestItemStatus::Requested->value,
            ]);

            app(ActivityLogService::class)->log(
                module: 'branch_request',
                action: 'submit',
                subject: $request,
                before: $before,
                after: $request->fresh()->toArray(),
                actor: $actor,
                branchId: $request->branch_id,
                description: 'Cabang submit request barang',
            );

            $this->notifyWarehouse('Request baru dari cabang '.$request->branch?->name, $request);

            return $request->refresh();
        });
    }

    public function review(BranchRequest $request, User $actor): BranchRequest
    {
        $this->assertWarehouseActor($actor, 'review');
        $this->assertCanTransition($request, [BranchRequestStatus::Submitted], 'review');

        return $this->updateStatus($request, BranchRequestStatus::Reviewed, $actor, ['reviewed_by' => $actor->id, 'reviewed_at' => now()], 'Gudang review request cabang');
    }

    public function approve(BranchRequest $request, User $actor): BranchRequest
    {
        $this->assertWarehouseActor($actor, 'approve');
        $this->assertCanTransition($request, [BranchRequestStatus::Submitted, BranchRequestStatus::Reviewed], 'approve');

        return DB::transaction(function () use ($request, $actor): BranchRequest {
            foreach ($request->items as $item) {
                if ((float) $item->approved_qty <= 0) {
                    $item->update([
                        'approved_qty' => $item->requested_qty,
                    ]);
                }

                $item->update([
                    'item_status' => BranchRequestItemStatus::Approved->value,
                ]);
            }

            $updated = $this->updateStatus(
                $request,
                BranchRequestStatus::Approved,
                $actor,
                ['approved_by' => $actor->id, 'approved_at' => now()],
                'Gudang approve request cabang',
                false,
            );

            $this->notifyBranch($updated, 'Request '.$updated->request_number.' disetujui gudang.');

            return $updated;
        });
    }

    public function reject(BranchRequest $request, User $actor, ?string $note = null): BranchRequest
    {
        $this->assertWarehouseActor($actor, 'reject');
        $this->assertCanTransition($request, [BranchRequestStatus::Submitted, BranchRequestStatus::Reviewed], 'reject');

        $updated = $this->updateStatus(
            $request,
            BranchRequestStatus::Rejected,
            $actor,
            [
                'note_warehouse' => $note ?? $request->note_warehouse,
            ],
            'Gudang reject request cabang',
            false,
        );

        $this->notifyBranch($updated, 'Request '.$updated->request_number.' ditolak gudang.');

        return $updated;
    }

    public function markPacked(BranchRequest $request, User $actor): BranchRequest
    {
        $this->assertWarehouseActor($actor, 'packing');
        $this->assertCanTransition($request, [BranchRequestStatus::Approved, BranchRequestStatus::Reviewed], 'packing');

        return DB::transaction(function () use ($request, $actor): BranchRequest {
            foreach ($request->items as $item) {
                $approved = (float) $item->approved_qty;
                $packed = (float) $item->packed_qty;

                if ($packed <= 0) {
                    $packed = $approved;
                }

                $status = $packed >= $approved
                    ? BranchRequestItemStatus::Packed
                    : BranchRequestItemStatus::Partial;

                $item->update([
                    'packed_qty' => $packed,
                    'item_status' => $status->value,
                ]);
            }

            return $this->updateStatus($request, BranchRequestStatus::Packed, $actor, ['packed_at' => now()], 'Gudang selesai packing request cabang');
        });
    }

    public function markShipped(BranchRequest $request, User $actor): BranchRequest
    {
        $this->assertWarehouseActor($actor, 'pengiriman');
        $this->assertCanTransition($request, [BranchRequestStatus::Approved, BranchRequestStatus::Packed], 'pengiriman');

        return DB::transaction(function () use ($request, $actor): BranchRequest {
            foreach ($request->items as $item) {
                $approved = (float) $item->approved_qty;
                $packed = max((float) $item->packed_qty, 0);
                $shipped = (float) $item->shipped_qty;

                if ($shipped <= 0) {
                    $shipped = $packed > 0 ? $packed : $approved;
                }

                $status = $shipped >= $approved
                    ? BranchRequestItemStatus::Shipped
                    : BranchRequestItemStatus::Partial;

                $item->update([
                    'shipped_qty' => $shipped,
                    'item_status' => $status->value,
                ]);
            }

            $updated = $this->updateStatus(
                $request,
                BranchRequestStatus::Shipped,
                $actor,
                ['shipped_by' => $actor->id, 'shipped_at' => now()],
                'Gudang kirim request cabang',
                false,
            );

            $this->notifyBranch($updated, 'Barang untuk request '.$updated->request_number.' telah dikirim.');

            return $updated;
        });
    }

    public function markReceived(BranchRequest $request, User $actor): BranchRequest
    {
        $this->assertCanTransition($request, [BranchRequestStatus::Shipped], 'penerimaan');

        if (! $actor->isAdminLike() && ! $actor->isWarehouseLike()) {
            if (! $actor->isBranchLike() || $actor->branch_id !== $request->branch_id) {
                throw new \RuntimeException('User cabang hanya boleh menerima request untuk cabangnya sendiri.');
            }
        }

        return DB::transaction(function () use ($request, $actor): BranchRequest {
            foreach ($request->items as $item) {
                $approved = (float) $item->approved_qty;
                $shipped = max((float) $item->shipped_qty, 0);
                $received = (float) $item->received_qty;

                if ($received <= 0) {
                    $received = $shipped;
                }

                $status = $received >= $approved
                    ? BranchRequestItemStatus::Received
                    : BranchRequestItemStatus::Partial;

                $item->update([
                    'received_qty' => $received,
                    'item_status' => $status->value,
                ]);
            }

            return $this->updateStatus(
                $request,
                BranchRequestStatus::Received,
                $actor,
                ['received_by' => $actor->id, 'received_at' => now()],
                'Cabang terima request barang',
            );
        });
    }

    protected function updateStatus(
        BranchRequest $request,
        BranchRequestStatus $status,
        User $actor,
        array $extra = [],
        string $description = '',
        bool $notifyWarehouse = true,
    ): BranchRequest {
        $before = $request->toArray();

        $request->update(array_merge(['status' => $status->value], $extra));

        app(ActivityLogService::class)->log(
            module: 'branch_request',
            action: $status->value,
            subject: $request,
            before: $before,
            after: $request->fresh()->toArray(),
            actor: $actor,
            branchId: $request->branch_id,
            description: $description,
        );

        if ($notifyWarehouse) {
            $this->notifyWarehouse('Update request '.$request->request_number.' menjadi '.str($status->value)->replace('_', ' ')->title(), $request);
        }

        return $request->refresh();
    }

    protected function makeNumber(): string
    {
        $prefix = 'REQ-'.now()->format('Ymd');
        $last = BranchRequest::query()
            ->where('request_number', 'like', $prefix.'-%')
            ->latest('id')
            ->value('request_number');

        $next = 1;
        if (is_string($last) && str_contains($last, '-')) {
            $next = (int) str($last)->afterLast('-')->toString() + 1;
        }

        return sprintf('%s-%04d', $prefix, $next);
    }

    /**
     * @param  array<int, BranchRequestStatus>  $allowed
     */
    protected function assertCanTransition(BranchRequest $request, array $allowed, string $action): void
    {
        $allowedValues = array_map(static fn (BranchRequestStatus $status): string => $status->value, $allowed);

        if (in_array($request->status, $allowedValues, true)) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'Aksi %s tidak valid untuk status %s.',
            $action,
            $request->status,
        ));
    }

    protected function assertWarehouseActor(User $actor, string $action): void
    {
        if ($actor->isAdminLike() || $actor->isWarehouseLike()) {
            return;
        }

        throw new \RuntimeException('Role user tidak memiliki izin untuk aksi '.$action.'.');
    }

    protected function notifyWarehouse(string $message, BranchRequest $request): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $users = User::query()->whereHas('roles', function ($query): void {
            $query->whereIn('name', ['gudang', 'head_logistics', 'logistics_admin', 'admin', 'owner']);
        })->get();

        foreach ($users as $user) {
            Notification::make()
                ->title('Branch Request Update')
                ->body($message)
                ->sendToDatabase($user);
        }
    }

    protected function notifyBranch(BranchRequest $request, string $message): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $users = User::query()
            ->where('branch_id', $request->branch_id)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['cabang', 'branch']))
            ->get();

        foreach ($users as $user) {
            Notification::make()
                ->title('Status Request Barang')
                ->body($message)
                ->sendToDatabase($user);
        }
    }
}
