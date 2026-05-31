<?php

namespace App\Policies;

use App\Enums\BranchRequestStatus;
use App\Models\BranchRequest;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByRole;

class BranchRequestPolicy
{
    use AuthorizesByRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwner($user) || $this->isGudang($user) || $this->isBranch($user);
    }

    public function view(User $user, BranchRequest $request): bool
    {
        if ($this->isOwner($user) || $this->isGudang($user)) {
            return true;
        }

        return $this->isBranch($user) && $user->branch_id === $request->branch_id;
    }

    public function create(User $user): bool
    {
        return $this->isOwner($user) || $this->isGudang($user) || $this->isBranch($user);
    }

    public function update(User $user, BranchRequest $request): bool
    {
        if ($this->isOwner($user) || $this->isGudang($user)) {
            return true;
        }

        if (! $this->isBranch($user) || $user->branch_id !== $request->branch_id) {
            return false;
        }

        return in_array($request->status, [
            BranchRequestStatus::Draft->value,
            BranchRequestStatus::Submitted->value,
            BranchRequestStatus::Reviewed->value,
        ], true);
    }

    public function delete(User $user, BranchRequest $request): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        return $this->isBranch($user)
            && $user->branch_id === $request->branch_id
            && in_array($request->status, [BranchRequestStatus::Draft->value, BranchRequestStatus::Rejected->value], true);
    }

    public function submit(User $user, BranchRequest $request): bool
    {
        return ($this->isOwner($user) || ($this->isBranch($user) && $user->branch_id === $request->branch_id))
            && $request->status === BranchRequestStatus::Draft->value;
    }

    public function review(User $user, BranchRequest $request): bool
    {
        return ($this->isOwner($user) || $this->isGudang($user))
            && $request->status === BranchRequestStatus::Submitted->value;
    }

    public function approve(User $user, BranchRequest $request): bool
    {
        return ($this->isOwner($user) || $this->isGudang($user))
            && in_array($request->status, [BranchRequestStatus::Submitted->value, BranchRequestStatus::Reviewed->value], true);
    }

    public function reject(User $user, BranchRequest $request): bool
    {
        return ($this->isOwner($user) || $this->isGudang($user))
            && in_array($request->status, [BranchRequestStatus::Submitted->value, BranchRequestStatus::Reviewed->value], true);
    }

    public function markPacked(User $user, BranchRequest $request): bool
    {
        return ($this->isOwner($user) || $this->isGudang($user))
            && in_array($request->status, [BranchRequestStatus::Approved->value, BranchRequestStatus::Reviewed->value], true);
    }

    public function markShipped(User $user, BranchRequest $request): bool
    {
        return ($this->isOwner($user) || $this->isGudang($user))
            && in_array($request->status, [BranchRequestStatus::Approved->value, BranchRequestStatus::Packed->value], true);
    }

    public function markReceived(User $user, BranchRequest $request): bool
    {
        if ($this->isOwner($user) || $this->isGudang($user)) {
            return $request->status === BranchRequestStatus::Shipped->value;
        }

        return $this->isBranch($user)
            && $user->branch_id === $request->branch_id
            && $request->status === BranchRequestStatus::Shipped->value;
    }
}
