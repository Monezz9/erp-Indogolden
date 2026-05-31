<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HppCalculationLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'hpp_calculation_id',
        'item_id',
        'line_type',
        'item_name',
        'purchase_price',
        'stock_awal',
        'stock_masuk',
        'stock_keluar',
        'hasil_bersih',
        'additional_cost',
        'total_value',
        'hpp_result',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'stock_awal' => 'decimal:3',
            'stock_masuk' => 'decimal:3',
            'stock_keluar' => 'decimal:3',
            'hasil_bersih' => 'decimal:3',
        ];
    }

    public function calculation(): BelongsTo
    {
        return $this->belongsTo(HppCalculation::class, 'hpp_calculation_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
