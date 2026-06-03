<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkInProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'process_number',
        'process_date',
        'process_type',
        'warehouse_id',
        'input_item_id',
        'output_item_id',
        'input_unit_id',
        'output_unit_id',
        'input_qty',
        'standard_conversion_per_unit',
        'expected_output_qty',
        'actual_output_qty',
        'variance_qty',
        'overhead_cost',
        'input_unit_cost',
        'total_input_cost',
        'output_unit_cost',
        'total_output_cost',
        'vendor_name',
        'status',
        'notes',
        'created_by',
        'posted_at',
    ];

    protected $casts = [
        'process_date' => 'date',
        'posted_at' => 'datetime',
        'input_qty' => 'decimal:4',
        'standard_conversion_per_unit' => 'decimal:4',
        'expected_output_qty' => 'decimal:4',
        'actual_output_qty' => 'decimal:4',
        'variance_qty' => 'decimal:4',
        'overhead_cost' => 'decimal:4',
        'input_unit_cost' => 'decimal:4',
        'total_input_cost' => 'decimal:4',
        'output_unit_cost' => 'decimal:4',
        'total_output_cost' => 'decimal:4',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inputItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'input_item_id');
    }

    public function outputItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'output_item_id');
    }

    public function inputUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'input_unit_id');
    }

    public function outputUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'output_unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
