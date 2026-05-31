<?php

namespace App\Policies;

use App\Enums\TransferStatus;
use App\Enums\UserRole;
use App\Models\Transfer;
use App\Policies\Concerns\AuthorizesByRole;
use App\Models\User;

class TransferPolicy
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
            UserRole::Branch,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transfer $model): bool
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
    public function update(User $user, Transfer $model): bool
    {
        return $this->create($user) && $model->status !== TransferStatus::Received;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transfer $model): bool
    {
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transfer $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transfer $model): bool
    {
        return $this->isOwner($user);
    }

    public function approve(User $user, Transfer $model): bool
    {
        return $model->status === TransferStatus::Submitted
            && $this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics]);
    }

    public function submit(User $user, Transfer $model): bool
    {
        return $model->status === TransferStatus::Draft && $this->create($user);
    }

    public function reject(User $user, Transfer $model): bool
    {
        return $model->status === TransferStatus::Submitted
            && $this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics]);
    }

    public function ship(User $user, Transfer $model): bool
    {
        return $model->status === TransferStatus::Approved
            && $this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics, UserRole::LogisticsAdmin]);
    }

    public function receive(User $user, Transfer $model): bool
    {
        if ($model->status !== TransferStatus::Shipped) {
            return false;
        }

        if ($this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics, UserRole::LogisticsAdmin])) {
            return true;
        }

        return $this->isBranch($user) && (int) $user->branch_id === (int) $model->to_branch_id;
    }
}
