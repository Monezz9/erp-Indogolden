<?php

namespace App\Policies;

use App\Enums\ProductionOrderStatus;
use App\Enums\UserRole;
use App\Models\ProductionOrder;
use App\Policies\Concerns\AuthorizesByRole;
use App\Models\User;

class ProductionOrderPolicy
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
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductionOrder $model): bool
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
    public function update(User $user, ProductionOrder $model): bool
    {
        return $this->create($user)
            && in_array($model->status, [ProductionOrderStatus::Draft, ProductionOrderStatus::Submitted], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductionOrder $model): bool
    {
        return $this->isOwner($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProductionOrder $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProductionOrder $model): bool
    {
        return $this->isOwner($user);
    }

    public function approve(User $user, ProductionOrder $model): bool
    {
        return $model->status === ProductionOrderStatus::Submitted
            && $this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics]);
    }

    public function submit(User $user, ProductionOrder $model): bool
    {
        return $model->status === ProductionOrderStatus::Draft && $this->create($user);
    }

    public function complete(User $user, ProductionOrder $model): bool
    {
        return in_array($model->status, [ProductionOrderStatus::Submitted, ProductionOrderStatus::Approved], true)
            && $this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics, UserRole::LogisticsAdmin]);
    }
}
