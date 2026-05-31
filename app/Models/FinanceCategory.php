<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class FinanceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_cogs',
        'is_active',
    ];

    protected $casts = [
        'is_cogs' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function incomes(): HasMany
    {
        return $this->hasMany(FinanceIncome::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(FinanceExpense::class);
    }
}
