<?php

namespace App\Policies;

use App\Enums\GoodsReceiptStatus;
use App\Enums\UserRole;
use App\Models\GoodsReceipt;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class GoodsReceiptPolicy
{
    use AuthorizesByRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwner($user) || $this->isGudang($user) || $user->hasRole(UserRole::Finance->value);
    }

    public function view(User $user, GoodsReceipt $receipt): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isOwner($user) || $this->isGudang($user);
    }

    public function update(User $user, GoodsReceipt $receipt): bool
    {
        return ($this->isOwner($user) || $this->isGudang($user))
            && $receipt->status === GoodsReceiptStatus::Draft;
    }

    public function confirm(User $user, GoodsReceipt $receipt): bool
    {
        return ($this->isOwner($user) || $this->isGudang($user))
            && $receipt->status === GoodsReceiptStatus::Draft;
    }
}
