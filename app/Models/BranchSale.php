<?php

namespace App\Models;

use App\Enums\BranchSaleStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class BranchSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_number',
        'sale_date',
        'branch_id',
        'cashier_id',
        'status',
        'payment_method',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'cogs_amount',
        'gross_profit',
        'notes',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'posted_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cogs_amount' => 'decimal:4',
        'gross_profit' => 'decimal:2',
        'status' => BranchSaleStatus::class,
        'payment_method' => PaymentMethod::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(Cashier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BranchSaleItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
