<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ProductionRecipeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_recipe_id',
        'item_id',
        'unit_id',
        'stage_id',
        'qty',
        'is_optional',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'is_optional' => 'boolean',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(ProductionRecipe::class, 'production_recipe_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ItemStage::class, 'stage_id');
    }
}
