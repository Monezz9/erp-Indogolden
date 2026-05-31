<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'balance_key',
        'item_id',
        'stage_id',
        'warehouse_id',
        'branch_id',
        'stock_batch_id',
        'qty_on_hand',
        'avg_cost',
        'total_value',
        'last_movement_item_id',
        'last_updated_at',
    ];

    protected $casts = [
        'qty_on_hand' => 'decimal:4',
        'avg_cost' => 'decimal:4',
        'total_value' => 'decimal:4',
        'last_updated_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ItemStage::class, 'stage_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }
}
