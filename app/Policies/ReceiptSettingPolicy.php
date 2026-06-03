<?php

namespace App\Policies;

use App\Models\ReceiptSetting;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class ReceiptSettingPolicy
{
    use AuthorizesByRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function view(User $user, ReceiptSetting $receiptSetting): bool
    {
        return $this->isOwner($user);
    }

    public function create(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function update(User $user, ReceiptSetting $receiptSetting): bool
    {
        return $this->isOwner($user);
    }

    public function delete(User $user, ReceiptSetting $receiptSetting): bool
    {
        return $this->isOwner($user);
    }

    public function restore(User $user, ReceiptSetting $receiptSetting): bool
    {
        return $this->isOwner($user);
    }

    public function forceDelete(User $user, ReceiptSetting $receiptSetting): bool
    {
        return $this->isOwner($user);
    }
}
