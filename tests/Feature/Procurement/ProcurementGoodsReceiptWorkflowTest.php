<?php

namespace Tests\Feature\Procurement;

use App\Enums\GoodsReceiptStatus;
use App\Enums\MovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GoodsReceiptService;
use App\Services\PurchaseOrderService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProcurementGoodsReceiptWorkflowTest extends TestCase
{
    public function test_confirm_goods_receipt_creates_inbound_purchase_stock_movement(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('gudang-procurement@erp.test', 'gudang');
        $unit = Unit::query()->create([
            'code' => 'KG',
            'name' => 'Kilogram',
            'is_base' => true,
            'precision' => 4,
            'is_active' => true,
        ]);
        $category = ItemCategory::query()->create([
            'name' => 'Raw Material',
            'slug' => 'raw-material-procurement',
            'category_type' => 'raw_material',
            'is_active' => true,
        ]);
        $stage = ItemStage::query()->create([
            'code' => 'raw_dirty',
            'name' => 'Raw Dirty',
            'sequence' => 1,
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'RM-PROC-001',
            'name' => 'Bahan Procurement',
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $stage->id,
            'item_type' => 'material',
            'requires_production' => false,
            'is_perishable' => false,
            'minimum_stock' => 0,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);
        $supplier = Supplier::query()->create([
            'code' => 'SUP-PROC',
            'name' => 'Supplier Procurement',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-PROC',
            'name' => 'Warehouse Procurement',
            'location_type' => 'central',
            'is_active' => true,
        ]);

        $poService = app(PurchaseOrderService::class);
        $purchaseOrder = $poService->createDraft([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
        ], [[
            'item_id' => $item->id,
            'unit_id' => $unit->id,
            'ordered_qty' => 12,
            'unit_cost' => 25000,
        ]], $user);

        $purchaseOrder = $poService->submit($purchaseOrder, $user);
        $purchaseOrder = $poService->financeApprove($purchaseOrder, $user);

        $receipt = app(GoodsReceiptService::class)->createDraftFromPurchaseOrder($purchaseOrder, $user);
        $receipt = app(GoodsReceiptService::class)->confirm($receipt, $user);

        $this->assertSame(GoodsReceiptStatus::Confirmed, $receipt->status);
        $this->assertSame(PurchaseOrderStatus::Received, $receipt->purchaseOrder->status);
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => MovementType::InboundPurchase->value,
            'reference_type' => $receipt::class,
            'reference_id' => $receipt->id,
        ]);

        $movement = StockMovement::query()->whereMorphedTo('reference', $receipt)->firstOrFail();
        $this->assertSame(12.0, (float) $movement->items()->firstOrFail()->qty);

        $balance = StockBalance::query()
            ->where('item_id', $item->id)
            ->where('stage_id', $stage->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        $this->assertSame(12.0, (float) $balance->qty_on_hand);
    }

    protected function createUserWithRole(string $email, string $role): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create([
            'email' => $email,
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    protected function prepareDatabase(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ekstensi pdo_sqlite belum tersedia pada environment ini.');
        }

        $this->artisan('migrate:fresh');
    }
}
