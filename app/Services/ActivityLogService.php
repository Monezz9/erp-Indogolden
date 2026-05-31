<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function log(
        string $module,
        string $action,
        Model|string|null $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?User $actor = null,
        ?int $branchId = null,
        ?string $description = null,
    ): ActivityLog {
        $subjectType = null;
        $subjectId = null;

        if ($subject instanceof Model) {
            $subjectType = $subject::class;
            $subjectId = $subject->getKey();
        }

        $actor ??= Auth::user();

        return ActivityLog::query()->create([
            'user_id' => $actor?->id,
            'branch_id' => $branchId ?? $actor?->branch_id,
            'module' => $module,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before_data' => $before,
            'after_data' => $after,
            'description' => $description,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'logged_at' => now(),
        ]);
    }
}
