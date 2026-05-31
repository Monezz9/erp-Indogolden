<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ProductionOrderOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'item_id',
        'unit_id',
        'stage_id',
        'warehouse_id',
        'stock_movement_item_id',
        'qty',
        'unit_cost',
        'total_cost',
        'is_byproduct',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'is_byproduct' => 'boolean',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
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
