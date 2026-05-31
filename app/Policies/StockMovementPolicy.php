<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\StockMovement;
use App\Policies\Concerns\AuthorizesByRole;
use App\Models\User;

class StockMovementPolicy
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
    public function view(User $user, StockMovement $model): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        if ($this->isBranch($user)) {
            return (int) $model->from_branch_id === (int) $user->branch_id
                || (int) $model->to_branch_id === (int) $user->branch_id;
        }

        return $this->viewAny($user);
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
    public function update(User $user, StockMovement $model): bool
    {
        return $this->create($user)
            && in_array($model->status, [ApprovalStatus::Draft, ApprovalStatus::Submitted], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StockMovement $model): bool
    {
        return $this->isOwner($user) && $model->status === ApprovalStatus::Draft;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StockMovement $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StockMovement $model): bool
    {
        return $this->isOwner($user);
    }

    public function approve(User $user, StockMovement $model): bool
    {
        return $model->status === ApprovalStatus::Submitted
            && $this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics]);
    }

    public function submit(User $user, StockMovement $model): bool
    {
        return $model->status === ApprovalStatus::Draft && $this->create($user);
    }

    public function reject(User $user, StockMovement $model): bool
    {
        return $model->status === ApprovalStatus::Submitted
            && $this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics]);
    }
}
