<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HppCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'calc_number',
        'calc_date',
        'branch_id',
        'created_by',
        'stage',
        'product_name',
        'total_raw_value',
        'total_clean_value',
        'total_production_cost',
        'hpp_per_unit',
        'selling_price',
        'profit',
        'margin_percent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'calc_date' => 'date',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(HppCalculationLine::class, 'hpp_calculation_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
