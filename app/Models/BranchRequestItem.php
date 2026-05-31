<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'product_id',
        'category',
        'requested_qty',
        'approved_qty',
        'packed_qty',
        'shipped_qty',
        'received_qty',
        'unit_id',
        'stock_available',
        'branch_note',
        'warehouse_note',
        'item_status',
        'substitute_product_id',
    ];

    protected function casts(): array
    {
        return [
            'requested_qty' => 'decimal:3',
            'approved_qty' => 'decimal:3',
            'packed_qty' => 'decimal:3',
            'shipped_qty' => 'decimal:3',
            'received_qty' => 'decimal:3',
            'stock_available' => 'decimal:3',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(BranchRequest::class, 'request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'product_id');
    }

    public function substituteProduct(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'substitute_product_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
