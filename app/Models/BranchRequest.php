<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'branch_id',
        'request_date',
        'delivery_date',
        'status',
        'note_branch',
        'note_warehouse',
        'created_by',
        'reviewed_by',
        'approved_by',
        'shipped_by',
        'received_by',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'packed_at',
        'shipped_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'delivery_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'packed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BranchRequestItem::class, 'request_id');
    }

    public function scopeByRoleAndBranch(Builder $query, User $user): Builder
    {
        if ($user->isAdminLike() || $user->isWarehouseLike()) {
            return $query;
        }

        if ($user->isBranchLike() && $user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
