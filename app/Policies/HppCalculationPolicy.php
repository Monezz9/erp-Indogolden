<?php

namespace App\Policies;

use App\Models\HppCalculation;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class HppCalculationPolicy
{
    use AuthorizesByRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwner($user) || $this->isGudang($user);
    }

    public function view(User $user, HppCalculation $calc): bool
    {
        return $this->isOwner($user) || $this->isGudang($user);
    }

    public function create(User $user): bool
    {
        return $this->isOwner($user) || $this->isGudang($user);
    }

    public function update(User $user, HppCalculation $calc): bool
    {
        return $this->isOwner($user) || $this->isGudang($user);
    }

    public function delete(User $user, HppCalculation $calc): bool
    {
        return $this->isOwner($user);
    }
}
