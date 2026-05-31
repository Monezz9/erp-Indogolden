<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'item_id',
        'supplier_id',
        'warehouse_id',
        'received_at',
        'expired_at',
        'qty_initial',
        'qty_remaining',
        'unit_cost',
        'status',
        'notes',
    ];

    protected $casts = [
        'received_at' => 'date',
        'expired_at' => 'date',
        'qty_initial' => 'decimal:4',
        'qty_remaining' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
