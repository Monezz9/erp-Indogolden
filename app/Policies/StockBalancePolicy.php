<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\StockBalance;
use App\Policies\Concerns\AuthorizesByRole;
use App\Models\User;

class StockBalancePolicy
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
    public function view(User $user, StockBalance $model): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        if ($this->isBranch($user)) {
            return (int) $model->branch_id === (int) $user->branch_id;
        }

        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StockBalance $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StockBalance $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StockBalance $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StockBalance $model): bool
    {
        return false;
    }
}
