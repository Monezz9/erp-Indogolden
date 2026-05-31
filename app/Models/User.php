<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'branch_id',
        'phone',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasAnyRole(UserRole::values());
    }

    public function isAdminLike(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin->value,
            UserRole::Owner->value,
        ]);
    }

    public function isWarehouseLike(): bool
    {
        return $this->hasAnyRole([
            UserRole::Gudang->value,
            UserRole::HeadLogistics->value,
            UserRole::LogisticsAdmin->value,
        ]);
    }

    public function isBranchLike(): bool
    {
        return $this->hasAnyRole([
            UserRole::Cabang->value,
            UserRole::Branch->value,
        ]);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdStockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    public function createdSales(): HasMany
    {
        return $this->hasMany(BranchSale::class, 'created_by');
    }

    public function createdBranchRequests(): HasMany
    {
        return $this->hasMany(BranchRequest::class, 'created_by');
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(ImportLog::class, 'imported_by');
    }

    public function hppCalculations(): HasMany
    {
        return $this->hasMany(HppCalculation::class, 'created_by');
    }

    public function scopeByRoleAndBranch(Builder $query, User $authUser): Builder
    {
        if ($authUser->isAdminLike() || $authUser->hasRole(UserRole::HeadLogistics->value)) {
            return $query;
        }

        if ($authUser->isBranchLike() && $authUser->branch_id !== null) {
            return $query->where('branch_id', $authUser->branch_id);
        }

        return $query;
    }
}
