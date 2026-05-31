<?php

namespace App\Models;

use App\Enums\ProductionOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'production_recipe_id',
        'status',
        'planned_date',
        'started_at',
        'completed_at',
        'output_item_id',
        'output_unit_id',
        'warehouse_id',
        'target_qty',
        'actual_qty',
        'shrinkage_qty',
        'total_input_cost',
        'total_output_cost',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'status' => ProductionOrderStatus::class,
        'planned_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'approved_at' => 'datetime',
        'target_qty' => 'decimal:4',
        'actual_qty' => 'decimal:4',
        'shrinkage_qty' => 'decimal:4',
        'total_input_cost' => 'decimal:4',
        'total_output_cost' => 'decimal:4',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(ProductionRecipe::class, 'production_recipe_id');
    }

    public function outputItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'output_item_id');
    }

    public function outputUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'output_unit_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inputs(): HasMany
    {
        return $this->hasMany(ProductionOrderInput::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionOrderOutput::class);
    }
}
