<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Item;
use App\Policies\Concerns\AuthorizesByRole;
use App\Models\User;

class ItemPolicy
{
    use AuthorizesByRole;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [
            UserRole::Owner,
            UserRole::HeadLogistics,
            UserRole::LogisticsAdmin,
            UserRole::Finance,
            UserRole::Branch,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Item $model): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics, UserRole::LogisticsAdmin]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Item $model): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Item $model): bool
    {
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Item $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Item $model): bool
    {
        return false;
    }
}
