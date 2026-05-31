<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ProductionRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'output_item_id',
        'output_unit_id',
        'output_qty',
        'yield_percentage',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'output_qty' => 'decimal:4',
        'yield_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function outputItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'output_item_id');
    }

    public function outputUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'output_unit_id');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(ProductionRecipeItem::class);
    }
}
