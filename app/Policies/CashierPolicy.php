<?php

namespace App\Policies;

use App\Models\Cashier;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class CashierPolicy
{
    use AuthorizesByRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function view(User $user, Cashier $cashier): bool
    {
        return $this->isOwner($user);
    }

    public function create(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function update(User $user, Cashier $cashier): bool
    {
        return $this->isOwner($user);
    }

    public function delete(User $user, Cashier $cashier): bool
    {
        return $this->isOwner($user);
    }

    public function restore(User $user, Cashier $cashier): bool
    {
        return $this->isOwner($user);
    }

    public function forceDelete(User $user, Cashier $cashier): bool
    {
        return $this->isOwner($user);
    }
}
