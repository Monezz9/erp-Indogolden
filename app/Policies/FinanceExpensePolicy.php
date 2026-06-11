<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FinanceExpense;
use App\Policies\Concerns\AuthorizesByRole;
use App\Models\User;

class FinanceExpensePolicy
{
    use AuthorizesByRole;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->isOwner($user) || $user->hasRole(UserRole::Finance->value);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FinanceExpense $model): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isOwner($user) || $user->hasRole(UserRole::Finance->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FinanceExpense $model): bool
    {
        return $this->create($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FinanceExpense $model): bool
    {
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FinanceExpense $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FinanceExpense $model): bool
    {
        return false;
    }
}
