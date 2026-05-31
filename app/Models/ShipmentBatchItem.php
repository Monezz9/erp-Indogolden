<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_batch_id',
        'request_id',
        'request_item_id',
        'branch_id',
        'product_id',
        'qty_to_ship',
        'qty_packed',
        'qty_shipped',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'qty_to_ship' => 'decimal:3',
            'qty_packed' => 'decimal:3',
            'qty_shipped' => 'decimal:3',
        ];
    }

    public function shipmentBatch(): BelongsTo
    {
        return $this->belongsTo(ShipmentBatch::class, 'shipment_batch_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(BranchRequest::class, 'request_id');
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(BranchRequestItem::class, 'request_item_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'product_id');
    }
}
