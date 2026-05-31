<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;
use App\Models\Warehouse;

class WarehousePolicy
{
    use AuthorizesByRole;

    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, [
            UserRole::Owner,
            UserRole::HeadLogistics,
            UserRole::LogisticsAdmin,
            UserRole::Branch,
        ]);
    }

    public function view(User $user, Warehouse $model): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        if ($this->isBranch($user)) {
            return $model->branch_id === null || (int) $model->branch_id === (int) $user->branch_id;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->hasRole($user, [UserRole::Owner, UserRole::HeadLogistics, UserRole::LogisticsAdmin]);
    }

    public function update(User $user, Warehouse $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Warehouse $model): bool
    {
        return $this->isOwner($user);
    }

    public function restore(User $user, Warehouse $model): bool
    {
        return $this->isOwner($user);
    }

    public function forceDelete(User $user, Warehouse $model): bool
    {
        return $this->isOwner($user);
    }
}
