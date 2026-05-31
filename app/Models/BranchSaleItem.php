<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class BranchSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_sale_id',
        'item_id',
        'unit_id',
        'qty',
        'unit_price',
        'line_total',
        'cogs_unit',
        'cogs_total',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'cogs_unit' => 'decimal:4',
        'cogs_total' => 'decimal:4',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(BranchSale::class, 'branch_sale_id');
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
