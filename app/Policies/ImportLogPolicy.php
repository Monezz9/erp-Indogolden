<?php

namespace App\Policies;

use App\Models\ImportLog;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class ImportLogPolicy
{
    use AuthorizesByRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwner($user) || $this->isGudang($user);
    }

    public function view(User $user, ImportLog $log): bool
    {
        return $this->isOwner($user) || $this->isGudang($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ImportLog $log): bool
    {
        return false;
    }

    public function delete(User $user, ImportLog $log): bool
    {
        return false;
    }
}
