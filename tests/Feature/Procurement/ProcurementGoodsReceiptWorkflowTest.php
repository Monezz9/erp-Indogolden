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
use App\Filament\Pages\ProcurementRequestWorkspace;
use App\Services\GoodsReceiptService;
use App\Services\PurchaseOrderService;
use Livewire\Livewire;
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

    public function test_purchase_receipt_converts_purchase_unit_to_base_stock_unit(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('gudang-conversion@erp.test', 'gudang');
        $kg = Unit::query()->updateOrCreate(['code' => 'KG'], [
            'name' => 'Kilogram',
            'is_base' => false,
            'precision' => 4,
            'is_active' => true,
        ]);
        $gram = Unit::query()->updateOrCreate(['code' => 'GR'], [
            'name' => 'Gram',
            'is_base' => true,
            'precision' => 4,
            'is_active' => true,
        ]);
        $category = ItemCategory::query()->create([
            'name' => 'Raw Material',
            'slug' => 'raw-material-conversion',
            'category_type' => 'raw_material',
            'is_active' => true,
        ]);
        $stage = ItemStage::query()->updateOrCreate(['code' => 'raw_clean'], [
            'name' => 'Raw Clean',
            'sequence' => 2,
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'RM-BAPUT-TEST',
            'name' => 'Bawang Putih',
            'item_category_id' => $category->id,
            'default_unit_id' => $gram->id,
            'default_stage_id' => $stage->id,
            'item_type' => 'material',
            'requires_production' => false,
            'is_perishable' => false,
            'minimum_stock' => 0,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);
        $supplier = Supplier::query()->create([
            'code' => 'SUP-CONV',
            'name' => 'Supplier Konversi',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-CONV',
            'name' => 'Warehouse Konversi',
            'location_type' => 'central',
            'is_active' => true,
        ]);

        $poService = app(PurchaseOrderService::class);
        $purchaseOrder = $poService->createDraft([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
        ], [[
            'item_id' => $item->id,
            'unit_id' => $gram->id,
            'purchase_unit_id' => $kg->id,
            'purchase_qty' => 3,
            'conversion_qty' => 1000,
            'ordered_qty' => 3000,
            'line_total' => 118000,
            'purchase_unit_cost' => 118000 / 3,
        ]], $user);

        $purchaseOrder = $poService->submit($purchaseOrder, $user);
        $purchaseOrder = $poService->financeApprove($purchaseOrder, $user);

        $receipt = app(GoodsReceiptService::class)->createDraftFromPurchaseOrder($purchaseOrder, $user);
        $receipt = app(GoodsReceiptService::class)->confirm($receipt, $user);

        $movementLine = StockMovement::query()
            ->whereMorphedTo('reference', $receipt)
            ->firstOrFail()
            ->items()
            ->firstOrFail();

        $this->assertSame(3000.0, (float) $movementLine->qty);
        $this->assertSame($gram->id, $movementLine->unit_id);
        $this->assertEqualsWithDelta(39.3333, (float) $movementLine->unit_cost, 0.0001);

        $balance = StockBalance::query()
            ->where('item_id', $item->id)
            ->where('stage_id', $stage->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        $this->assertSame(3000.0, (float) $balance->qty_on_hand);
        $this->assertEqualsWithDelta(39.3333, (float) $balance->avg_cost, 0.0001);
        $this->assertEqualsWithDelta(118000.0, (float) $balance->total_value, 0.2);
    }

    public function test_procurement_workspace_selects_item_from_search_result(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('gudang-search@erp.test', 'gudang');
        $this->actingAs($user);

        $kg = Unit::query()->updateOrCreate(['code' => 'KG'], [
            'name' => 'Kilogram',
            'is_base' => false,
            'precision' => 4,
            'is_active' => true,
        ]);
        $gram = Unit::query()->updateOrCreate(['code' => 'GR'], [
            'name' => 'Gram',
            'is_base' => true,
            'precision' => 4,
            'is_active' => true,
        ]);
        $category = ItemCategory::query()->updateOrCreate(['slug' => 'raw-material-search'], [
            'name' => 'Raw Material',
            'category_type' => 'raw_material',
            'is_active' => true,
        ]);
        $stage = ItemStage::query()->updateOrCreate(['code' => 'raw_dirty'], [
            'name' => 'Raw Dirty',
            'sequence' => 1,
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'CA0001',
            'name' => 'Cabe Kriting',
            'item_category_id' => $category->id,
            'default_unit_id' => $gram->id,
            'default_stage_id' => $stage->id,
            'item_type' => 'material',
            'requires_production' => false,
            'is_perishable' => true,
            'minimum_stock' => 0,
            'purchase_price' => 0,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);

        Livewire::test(ProcurementRequestWorkspace::class)
            ->set('itemSearch', 'CA')
            ->call('openItemSearchResults')
            ->assertSet('showItemSearchResults', true)
            ->assertSet('itemId', null)
            ->call('selectItem', $item->id)
            ->assertSet('itemId', $item->id)
            ->assertSet('showItemSearchResults', false)
            ->assertSet('itemSearch', 'CA0001 - Cabe Kriting')
            ->assertSet('itemKind', 'Raw Material')
            ->assertSet('purchaseUnitId', $kg->id)
            ->assertSet('unitId', $gram->id)
            ->assertSet('conversionQty', 1000.0);
    }

    public function test_procurement_workspace_search_finds_raw_material_by_rm_alias(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('gudang-rm-search@erp.test', 'gudang');
        $this->actingAs($user);

        $gram = Unit::query()->updateOrCreate(['code' => 'GR'], [
            'name' => 'Gram',
            'is_base' => true,
            'precision' => 4,
            'is_active' => true,
        ]);
        $category = ItemCategory::query()->updateOrCreate(['slug' => 'raw-material-rm-search'], [
            'name' => 'Raw Material',
            'category_type' => 'raw_material',
            'is_active' => true,
        ]);
        $stage = ItemStage::query()->updateOrCreate(['code' => 'raw_dirty'], [
            'name' => 'Raw Dirty',
            'sequence' => 1,
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'CA0002',
            'name' => 'Cabe Besar',
            'item_category_id' => $category->id,
            'default_unit_id' => $gram->id,
            'default_stage_id' => $stage->id,
            'item_type' => 'material',
            'requires_production' => false,
            'is_perishable' => true,
            'minimum_stock' => 0,
            'purchase_price' => 0,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);

        $component = Livewire::test(ProcurementRequestWorkspace::class)
            ->set('itemSearch', 'RM')
            ->call('openItemSearchResults');

        $this->assertContains($item->id, collect($component->instance()->itemSearchResults())->pluck('id')->all());
    }

    public function test_procurement_workspace_only_allows_rm_and_srm_items_in_draft(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('gudang-category-guard@erp.test', 'gudang');
        $this->actingAs($user);

        $unit = Unit::query()->updateOrCreate(['code' => 'PCS'], [
            'name' => 'Pieces',
            'is_base' => true,
            'precision' => 0,
            'is_active' => true,
        ]);

        $rm = $this->createProcurementItem('RM-ALLOW-TEST', 'RM Allow Test', 'raw-material', 'Raw Material', 'raw_material', 'raw_dirty', $unit);
        $srm = $this->createProcurementItem('SRM-ALLOW-TEST', 'SRM Allow Test', 'srm', 'SRM', 'wip', 'srm', $unit);
        $fg = $this->createProcurementItem('FG-DENY-TEST', 'FG Deny Test', 'finished-goods', 'Finished Goods', 'finished_goods', 'finished_goods', $unit);
        $premix = $this->createProcurementItem('PMX-DENY-TEST', 'Premix Deny Test', 'premix', 'Premix', 'wip', 'raw_clean', $unit, 'premix');
        $rc = $this->createProcurementItem('RC-DENY-TEST', 'RC Deny Test', 'raw-clean', 'Raw Clean', 'raw_material', 'raw_clean', $unit);
        $wip = $this->createProcurementItem('WIP-DENY-TEST', 'WIP Deny Test', 'wip', 'WIP', 'wip', 'wip', $unit);

        foreach ([$rm, $srm] as $item) {
            Livewire::test(ProcurementRequestWorkspace::class)
                ->set('itemId', $item->id)
                ->set('unitId', $unit->id)
                ->set('purchaseUnitId', $unit->id)
                ->set('purchaseQty', 1)
                ->set('conversionQty', 1)
                ->set('unitCost', 1000)
                ->call('addItemToCart')
                ->assertSet('cart.0.item_id', $item->id);
        }

        foreach ([$fg, $premix, $rc, $wip] as $item) {
            $component = Livewire::test(ProcurementRequestWorkspace::class)
                ->set('itemId', $item->id)
                ->set('unitId', $unit->id)
                ->set('purchaseUnitId', $unit->id)
                ->set('purchaseQty', 1)
                ->set('conversionQty', 1)
                ->set('unitCost', 1000)
                ->call('addItemToCart');

            $this->assertSame([], $component->instance()->cart);
        }
    }

    public function test_procurement_workspace_blocks_save_when_cart_contains_invalid_category(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('gudang-invalid-save@erp.test', 'gudang');
        $this->actingAs($user);

        $unit = Unit::query()->updateOrCreate(['code' => 'PCS'], [
            'name' => 'Pieces',
            'is_base' => true,
            'precision' => 0,
            'is_active' => true,
        ]);
        $supplier = Supplier::query()->create([
            'code' => 'SUP-INVALID-SAVE',
            'name' => 'Supplier Invalid Save',
            'is_active' => true,
        ]);
        Warehouse::query()->create([
            'code' => 'WH-CENTRAL',
            'name' => 'Gudang Pusat',
            'location_type' => 'central',
            'is_active' => true,
        ]);
        $fg = $this->createProcurementItem('FG-CART-DENY', 'FG Cart Deny', 'finished-goods', 'Finished Goods', 'finished_goods', 'finished_goods', $unit);

        Livewire::test(ProcurementRequestWorkspace::class)
            ->set('supplierId', $supplier->id)
            ->set('cart', [[
                'item_id' => $fg->id,
                'item_label' => 'FG-CART-DENY - FG Cart Deny',
                'item_name' => 'FG Cart Deny',
                'item_kind' => 'Finished Goods',
                'unit_id' => $unit->id,
                'unit_label' => 'PCS - Pieces',
                'purchase_unit_id' => $unit->id,
                'purchase_unit_label' => 'PCS - Pieces',
                'purchase_qty' => 1,
                'conversion_qty' => 1,
                'ordered_qty' => 1,
                'line_total' => 1000,
                'purchase_unit_cost' => 1000,
                'unit_cost' => 1000,
                'notes' => null,
            ]])
            ->call('createPurchaseOrder');

        $this->assertDatabaseMissing('purchase_orders', [
            'supplier_id' => $supplier->id,
        ]);
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

    protected function createProcurementItem(
        string $sku,
        string $name,
        string $categorySlug,
        string $categoryName,
        string $categoryType,
        string $stageCode,
        Unit $unit,
        string $itemType = 'material',
    ): Item {
        $category = ItemCategory::query()->updateOrCreate(['slug' => $categorySlug], [
            'name' => $categoryName,
            'category_type' => $categoryType,
            'is_active' => true,
        ]);
        $stage = ItemStage::query()->updateOrCreate(['code' => $stageCode], [
            'name' => str($stageCode)->replace('_', ' ')->title()->toString(),
            'sequence' => 1,
            'is_active' => true,
        ]);

        return Item::query()->create([
            'sku' => $sku,
            'name' => $name,
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $stage->id,
            'item_type' => $itemType,
            'requires_production' => false,
            'is_perishable' => false,
            'minimum_stock' => 0,
            'purchase_price' => 0,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);
    }

    protected function prepareDatabase(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ekstensi pdo_sqlite belum tersedia pada environment ini.');
        }

        $this->artisan('migrate:fresh');
    }
}
