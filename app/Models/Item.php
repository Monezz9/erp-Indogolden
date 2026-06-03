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

    public function substituteBranchRequestItems(): HasMany
    {
        return $this->hasMany(BranchRequestItem::class, 'substitute_product_id');
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    public function transferItems(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function productionRecipeItems(): HasMany
    {
        return $this->hasMany(ProductionRecipeItem::class);
    }

    public function productionRecipesAsOutput(): HasMany
    {
        return $this->hasMany(ProductionRecipe::class, 'output_item_id');
    }

    public function productionOrdersAsOutput(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'output_item_id');
    }

    public function productionOrderInputs(): HasMany
    {
        return $this->hasMany(ProductionOrderInput::class);
    }

    public function productionOrderOutputs(): HasMany
    {
        return $this->hasMany(ProductionOrderOutput::class);
    }

    public function hppCalculationLines(): HasMany
    {
        return $this->hasMany(HppCalculationLine::class);
    }

    public function shipmentBatchItems(): HasMany
    {
        return $this->hasMany(ShipmentBatchItem::class, 'product_id');
    }

    public function cleaningProcesses(): HasMany
    {
        return $this->hasMany(CleaningProcess::class);
    }

    public function cleaningProcessesAsOutput(): HasMany
    {
        return $this->hasMany(CleaningProcess::class, 'output_item_id');
    }

    public function canBeDeleted(): bool
    {
        return ! $this->stockMovementItems()->exists()
            && ! $this->stockBalances()->exists()
            && ! $this->stockBatches()->exists()
            && ! $this->branchSaleItems()->exists()
            && ! $this->branchRequestItems()->exists()
            && ! $this->substituteBranchRequestItems()->exists()
            && ! $this->transferItems()->exists()
            && ! $this->purchaseOrderItems()->exists()
            && ! $this->goodsReceiptItems()->exists()
            && ! $this->productionRecipeItems()->exists()
            && ! $this->productionRecipesAsOutput()->exists()
            && ! $this->productionOrdersAsOutput()->exists()
            && ! $this->productionOrderInputs()->exists()
            && ! $this->productionOrderOutputs()->exists()
            && ! $this->hppCalculationLines()->exists()
            && ! $this->shipmentBatchItems()->exists()
            && ! $this->cleaningProcesses()->exists()
            && ! $this->cleaningProcessesAsOutput()->exists();
    }
}
