<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Policies\Concerns\AuthorizesByRole;
use App\Models\User;

class SupplierPolicy
{
    use AuthorizesByRole;

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics, UserRole::LogisticsAdmin]);
    }

    public function view(User $user, Supplier $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Supplier $model): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Supplier $model): bool
    {
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Supplier $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Supplier $model): bool
    {
        return false;
    }
}
