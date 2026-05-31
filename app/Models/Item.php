<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'item_category_id',
        'default_unit_id',
        'default_stage_id',
        'item_type',
        'requires_production',
        'is_perishable',
        'minimum_stock',
        'purchase_price',
        'latest_weighted_avg_cost',
        'selling_price',
        'description',
        'is_active',
    ];

    protected $casts = [
        'requires_production' => 'boolean',
        'is_perishable' => 'boolean',
        'minimum_stock' => 'decimal:4',
        'purchase_price' => 'decimal:4',
        'latest_weighted_avg_cost' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function defaultUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'default_unit_id');
    }

    public function defaultStage(): BelongsTo
    {
        return $this->belongsTo(ItemStage::class, 'default_stage_id');
    }

    public function stockMovementItems(): HasMany
    {
        return $this->hasMany(StockMovementItem::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function branchSaleItems(): HasMany
    {
        return $this->hasMany(BranchSaleItem::class);
    }

    public function branchRequestItems(): HasMany
    {
        return $this->hasMany(BranchRequestItem::class, 'product_id');
    }
}
