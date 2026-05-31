<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Policies\Concerns\AuthorizesByRole;
use App\Models\User;

class BranchPolicy
{
    use AuthorizesByRole;

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

    public function view(User $user, Branch $model): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        if ($this->isBranch($user)) {
            return (int) $user->branch_id === (int) $model->id;
        }

        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function update(User $user, Branch $model): bool
    {
        return $this->isOwner($user);
    }

    public function delete(User $user, Branch $model): bool
    {
        return $this->isOwner($user);
    }

    public function restore(User $user, Branch $model): bool
    {
        return $this->isOwner($user);
    }

    public function forceDelete(User $user, Branch $model): bool
    {
        return $this->isOwner($user);
    }
}
