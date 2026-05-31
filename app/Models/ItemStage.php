<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ItemStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'sequence',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class, 'stage_id');
    }
}
