<?php

namespace App\Policies;

use App\Enums\BranchSaleStatus;
use App\Enums\UserRole;
use App\Models\BranchSale;
use App\Policies\Concerns\AuthorizesByRole;
use App\Models\User;

class BranchSalePolicy
{
    use AuthorizesByRole;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [
            UserRole::Owner,
            UserRole::Finance,
            UserRole::HeadLogistics,
            UserRole::LogisticsAdmin,
            UserRole::Branch,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BranchSale $branchSale): bool
    {
        if ($this->isOwner($user) || $user->hasRole(UserRole::Finance->value) || $user->hasRole(UserRole::HeadLogistics->value)) {
            return true;
        }

        if ($this->isBranch($user)) {
            return (int) $user->branch_id === (int) $branchSale->branch_id;
        }

        return $user->hasRole(UserRole::LogisticsAdmin->value);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasRole($user, [
            UserRole::Owner,
            UserRole::HeadLogistics,
            UserRole::LogisticsAdmin,
            UserRole::Branch,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BranchSale $branchSale): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        if ($branchSale->status !== BranchSaleStatus::Draft) {
            return false;
        }

        if ($this->isBranch($user)) {
            return (int) $user->branch_id === (int) $branchSale->branch_id;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BranchSale $branchSale): bool
    {
        return $this->isOwner($user) && $branchSale->status === BranchSaleStatus::Draft;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BranchSale $branchSale): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BranchSale $branchSale): bool
    {
        return $this->isOwner($user);
    }

    public function post(User $user, BranchSale $branchSale): bool
    {
        if ($branchSale->status !== BranchSaleStatus::Draft) {
            return false;
        }

        if ($this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics, UserRole::LogisticsAdmin])) {
            return true;
        }

        return $this->isBranch($user) && (int) $user->branch_id === (int) $branchSale->branch_id;
    }

    public function cancel(User $user, BranchSale $branchSale): bool
    {
        if ($branchSale->status !== BranchSaleStatus::Draft) {
            return false;
        }

        return $this->post($user, $branchSale);
    }
}
