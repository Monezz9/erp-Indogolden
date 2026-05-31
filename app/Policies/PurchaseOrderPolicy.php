<?php

namespace App\Policies;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class PurchaseOrderPolicy
{
    use AuthorizesByRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwner($user) || $this->isGudang($user) || $this->isFinance($user);
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isOwner($user) || $this->isGudang($user);
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return ($this->isOwner($user) || $this->isGudang($user))
            && in_array($purchaseOrder->status, [
                PurchaseOrderStatus::Draft,
                PurchaseOrderStatus::FinanceRejected,
            ], true);
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->isOwner($user) && $purchaseOrder->status === PurchaseOrderStatus::Draft;
    }

    public function submit(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return ($this->isOwner($user) || $this->isGudang($user))
            && $purchaseOrder->status === PurchaseOrderStatus::Draft;
    }

    public function financeReview(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return ($this->isOwner($user) || $this->isFinance($user))
            && $purchaseOrder->status === PurchaseOrderStatus::Submitted;
    }

    public function receive(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return ($this->isOwner($user) || $this->isGudang($user))
            && in_array($purchaseOrder->status, [
                PurchaseOrderStatus::FinanceApproved,
                PurchaseOrderStatus::Ordered,
                PurchaseOrderStatus::PartiallyReceived,
            ], true);
    }

    protected function isFinance(User $user): bool
    {
        return $user->hasRole(UserRole::Finance->value);
    }
}
