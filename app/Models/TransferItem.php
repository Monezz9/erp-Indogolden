<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'item_id',
        'unit_id',
        'requested_qty',
        'approved_qty',
        'shipped_qty',
        'received_qty',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'requested_qty' => 'decimal:4',
        'approved_qty' => 'decimal:4',
        'shipped_qty' => 'decimal:4',
        'received_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
