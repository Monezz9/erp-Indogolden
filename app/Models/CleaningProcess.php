<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CleaningProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'process_number',
        'process_date',
        'warehouse_id',
        'item_id',
        'output_item_id',
        'unit_id',
        'input_qty',
        'output_qty',
        'shrinkage_qty',
        'shrinkage_percent',
        'input_unit_cost',
        'output_unit_cost',
        'total_input_cost',
        'status',
        'notes',
        'created_by',
        'posted_at',
    ];

    protected $casts = [
        'process_date' => 'date',
        'posted_at' => 'datetime',
        'input_qty' => 'decimal:4',
        'output_qty' => 'decimal:4',
        'shrinkage_qty' => 'decimal:4',
        'shrinkage_percent' => 'decimal:4',
        'input_unit_cost' => 'decimal:4',
        'output_unit_cost' => 'decimal:4',
        'total_input_cost' => 'decimal:4',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function outputItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'output_item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
