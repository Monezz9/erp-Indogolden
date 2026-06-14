<?php

namespace Tests\Feature\Procurement;

use App\Enums\GoodsReceiptStatus;
use App\Enums\MovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\FinanceExpense;
use App\Models\GoodsReceipt;
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
            ->assertSet('purchaseUnitId', $gram->id)
            ->assertSet('unitId', $gram->id)
            ->assertSet('conversionQty', 1.0);
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

    public function test_procurement_workspace_search_finds_all_active_raw_material_examples(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('gudang-rm-examples@erp.test', 'gudang');
        $this->actingAs($user);

        $unit = Unit::query()->updateOrCreate(['code' => 'GR'], [
            'name' => 'Gram',
            'is_base' => true,
            'precision' => 4,
            'is_active' => true,
        ]);
        $category = ItemCategory::query()->updateOrCreate(['slug' => 'raw-material'], [
            'name' => 'Raw Material',
            'category_type' => 'raw_material',
            'is_active' => true,
        ]);
        $stage = ItemStage::query()->updateOrCreate(['code' => 'raw_dirty'], [
            'name' => 'Raw Dirty',
            'sequence' => 1,
            'is_active' => true,
        ]);

        $items = collect([
            ['sku' => 'RM-KENCUR-SEARCH', 'name' => 'Kencur', 'hpp' => 33],
            ['sku' => 'RM-RAWIT-SEARCH', 'name' => 'Cabe Rawit', 'hpp' => 22],
            ['sku' => 'RM-KRITING-SEARCH', 'name' => 'Cabe Kriting', 'hpp' => 44],
        ])->map(fn (array $payload): Item => Item::query()->create([
            'sku' => $payload['sku'],
            'name' => $payload['name'],
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $stage->id,
            'item_type' => 'material',
            'requires_production' => false,
            'is_perishable' => false,
            'minimum_stock' => 0,
            'purchase_price' => 0,
            'latest_weighted_avg_cost' => $payload['hpp'],
            'is_active' => true,
        ]));

        StockBalance::query()->create([
            'balance_key' => implode(':', [$items[0]->id, $stage->id, 0, 0, 0]),
            'item_id' => $items[0]->id,
            'stage_id' => $stage->id,
            'qty_on_hand' => 12500,
            'avg_cost' => 33,
            'total_value' => 412500,
        ]);

        $kencurResults = Livewire::test(ProcurementRequestWorkspace::class)
            ->set('itemSearch', 'kencur')
            ->call('openItemSearchResults')
            ->instance()
            ->itemSearchResults();

        $this->assertContains($items[0]->id, collect($kencurResults)->pluck('id')->all());
        $kencurResult = collect($kencurResults)->firstWhere('id', $items[0]->id);
        $this->assertSame('Raw Material', $kencurResult['category']);
        $this->assertSame(12500.0, $kencurResult['stock_qty']);
        $this->assertSame('GR', $kencurResult['stock_unit']);
        $this->assertSame(33.0, $kencurResult['hpp']);

        $cabeResults = Livewire::test(ProcurementRequestWorkspace::class)
            ->set('itemSearch', 'cabe')
            ->call('openItemSearchResults')
            ->instance()
            ->itemSearchResults();

        $this->assertContains($items[1]->id, collect($cabeResults)->pluck('id')->all());
        $this->assertContains($items[2]->id, collect($cabeResults)->pluck('id')->all());

        $rawMaterialResults = Livewire::test(ProcurementRequestWorkspace::class)
            ->set('itemSearch', 'raw material')
            ->call('openItemSearchResults')
            ->instance()
            ->itemSearchResults();
        $rawMaterialIds = collect($rawMaterialResults)->pluck('id')->all();

        foreach ($items as $item) {
            $this->assertContains($item->id, $rawMaterialIds);
        }

        Livewire::test(ProcurementRequestWorkspace::class)
            ->set('itemSearch', 'kencur')
            ->call('selectItem', $items[0]->id)
            ->assertSet('itemId', $items[0]->id)
            ->assertSet('unitId', $unit->id)
            ->assertSet('purchaseUnitId', $unit->id);
    }

    public function test_procurement_workspace_does_not_duplicate_same_item_in_cart(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('gudang-duplicate-item@erp.test', 'gudang');
        $this->actingAs($user);

        $unit = Unit::query()->updateOrCreate(['code' => 'PCS'], [
            'name' => 'Pieces',
            'is_base' => true,
            'precision' => 0,
            'is_active' => true,
        ]);
        $item = $this->createProcurementItem('RM-DUP-TEST', 'RM Duplicate Test', 'raw-material', 'Raw Material', 'raw_material', 'raw_dirty', $unit);

        $component = Livewire::test(ProcurementRequestWorkspace::class)
            ->set('itemId', $item->id)
            ->set('unitId', $unit->id)
            ->set('purchaseUnitId', $unit->id)
            ->set('purchaseQty', 1)
            ->set('conversionQty', 1)
            ->set('unitCost', 1000)
            ->call('addItemToCart')
            ->set('itemId', $item->id)
            ->set('unitId', $unit->id)
            ->set('purchaseUnitId', $unit->id)
            ->set('purchaseQty', 3)
            ->set('conversionQty', 1)
            ->set('unitCost', 3000)
            ->call('addItemToCart');

        $this->assertCount(1, $component->instance()->cart);
        $this->assertSame(1.0, (float) $component->instance()->cart[0]['purchase_qty']);
    }

    public function test_procurement_workspace_can_save_draft_without_notes(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('gudang-no-notes@erp.test', 'gudang');
        $this->actingAs($user);

        $unit = Unit::query()->updateOrCreate(['code' => 'PCS'], [
            'name' => 'Pieces',
            'is_base' => true,
            'precision' => 0,
            'is_active' => true,
        ]);
        $supplier = Supplier::query()->create([
            'code' => 'SUP-NO-NOTES',
            'name' => 'Supplier No Notes',
            'is_active' => true,
        ]);
        $item = $this->createProcurementItem('RM-NO-NOTES', 'RM No Notes', 'raw-material', 'Raw Material', 'raw_material', 'raw_dirty', $unit);

        $component = Livewire::test(ProcurementRequestWorkspace::class)
            ->set('supplierId', $supplier->id)
            ->set('notes', null)
            ->set('cart', [[
                'item_id' => $item->id,
                'item_label' => 'RM-NO-NOTES - RM No Notes',
                'item_name' => 'RM No Notes',
                'item_kind' => 'Raw Material',
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
            ]]);

        $this->assertTrue($component->instance()->canSaveDraft());
    }

    public function test_procurement_workspace_allows_mixed_rm_srm_fg_and_operational_items_in_draft(): void
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
        $fg = $this->createProcurementItem('FG-ALLOW-TEST', 'FG Allow Test', 'finished-goods', 'Finished Goods', 'finished_goods', 'finished_goods', $unit);
        $operational = $this->createProcurementItem('MRO-ALLOW-TEST', 'Operasional Allow Test', 'mro', 'MRO', 'mro', 'mro', $unit, 'packaging');

        foreach ([$rm, $srm, $fg, $operational] as $item) {
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
        $invalid = $this->createProcurementItem('AN-CART-DENY', 'Analysis Cart Deny', 'analysis', 'Analysis', 'analysis', 'analysis', $unit);

        Livewire::test(ProcurementRequestWorkspace::class)
            ->set('supplierId', $supplier->id)
            ->set('notes', 'Catatan wajib')
            ->set('cart', [[
                'item_id' => $invalid->id,
                'item_label' => 'AN-CART-DENY - Analysis Cart Deny',
                'item_name' => 'Analysis Cart Deny',
                'item_kind' => 'Analysis',
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

    public function test_direct_procurement_requires_supplier(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('procurement-required@erp.test', 'gudang');
        [$gram, $kg, $warehouse, $item] = $this->basicProcurementFixture();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(GoodsReceiptService::class)->createDirectProcurement([
            'warehouse_id' => $warehouse->id,
            'receipt_date' => now()->toDateString(),
            'notes' => 'Catatan wajib',
        ], [[
            'item_id' => $item->id,
            'unit_id' => $gram->id,
            'purchase_unit_id' => $kg->id,
            'purchase_qty' => 1,
            'conversion_qty' => 1000,
            'line_total' => 35000,
            'notes' => 'Datang dari supplier',
        ]], $user);
    }

    public function test_direct_procurement_can_be_saved_without_notes(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('procurement-no-notes@erp.test', 'gudang');
        [$gram, $kg, $warehouse, $item, $supplier] = $this->basicProcurementFixture();

        $receipt = app(GoodsReceiptService::class)->createDirectProcurement([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'receipt_date' => now()->toDateString(),
        ], [[
            'item_id' => $item->id,
            'unit_id' => $gram->id,
            'purchase_unit_id' => $kg->id,
            'purchase_qty' => 1,
            'conversion_qty' => 1000,
            'line_total' => 35000,
        ]], $user);

        $this->assertSame(GoodsReceiptStatus::Confirmed, $receipt->status);
        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => $receipt::class,
            'reference_id' => $receipt->id,
        ]);
        $this->assertDatabaseHas('finance_expenses', [
            'reference_type' => $receipt::class,
            'reference_id' => $receipt->id,
        ]);
    }

    public function test_direct_procurement_kg_to_gram_creates_moving_average_finance_and_stock_movement(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('procurement-direct@erp.test', 'gudang');
        [$gram, $kg, $warehouse, $item, $supplier, $stage] = $this->basicProcurementFixture();

        StockBalance::query()->create([
            'balance_key' => implode(':', [$item->id, $stage->id, $warehouse->id, 0, 0]),
            'item_id' => $item->id,
            'stage_id' => $stage->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 1000,
            'avg_cost' => 30,
            'total_value' => 30000,
        ]);

        $receipt = app(GoodsReceiptService::class)->createDirectProcurement([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'receipt_date' => now()->toDateString(),
            'invoice_number' => 'INV-001',
            'notes' => 'Restock kencur',
        ], [[
            'item_id' => $item->id,
            'unit_id' => $gram->id,
            'purchase_unit_id' => $kg->id,
            'purchase_qty' => 1,
            'conversion_qty' => 1000,
            'line_total' => 35000,
            'notes' => 'Kencur datang',
        ]], $user);

        $this->assertSame(GoodsReceiptStatus::Confirmed, $receipt->status);
        $this->assertSame($supplier->id, $receipt->supplier_id);
        $this->assertEqualsWithDelta(35000, (float) $receipt->grand_total, 0.01);

        $line = $receipt->items()->firstOrFail();
        $this->assertSame(1000.0, (float) $line->received_qty);
        $this->assertEqualsWithDelta(35.0, (float) $line->unit_cost, 0.0001);

        $balance = StockBalance::query()
            ->where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        $this->assertSame(2000.0, (float) $balance->qty_on_hand);
        $this->assertEqualsWithDelta(65000, (float) $balance->total_value, 0.01);
        $this->assertEqualsWithDelta(32.5, (float) $balance->avg_cost, 0.0001);

        $movement = StockMovement::query()->whereMorphedTo('reference', $receipt)->firstOrFail();
        $this->assertSame(MovementType::InboundPurchase->value, $movement->movement_type->value ?? $movement->movement_type);
        $this->assertSame('in', $movement->items()->firstOrFail()->direction);

        $this->assertDatabaseHas('finance_expenses', [
            'supplier_id' => $supplier->id,
            'reference_type' => $receipt::class,
            'reference_id' => $receipt->id,
        ]);
    }

    public function test_direct_procurement_accepts_mixed_categories_and_operational_stock(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('procurement-mixed@erp.test', 'gudang');
        $unit = Unit::query()->updateOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'is_base' => true, 'precision' => 0, 'is_active' => true]);
        $warehouse = Warehouse::query()->create(['code' => 'WH-MIX', 'name' => 'Warehouse Mix', 'location_type' => 'central', 'is_active' => true]);
        $supplier = Supplier::query()->create(['code' => 'SUP-MIX', 'name' => 'Supplier Mix', 'is_active' => true]);

        $items = [
            $this->createProcurementItem('RM-MIX', 'RM Mix', 'raw-material', 'Raw Material', 'raw_material', 'raw_dirty', $unit),
            $this->createProcurementItem('SRM-MIX', 'SRM Mix', 'srm', 'SRM', 'wip', 'srm', $unit),
            $this->createProcurementItem('FG-MIX', 'FG Mix', 'finished-goods', 'Finished Goods', 'finished_goods', 'finished_goods', $unit),
            $this->createProcurementItem('MRO-MIX', 'Operasional Mix', 'mro', 'MRO', 'mro', 'mro', $unit, 'packaging'),
        ];

        $receipt = app(GoodsReceiptService::class)->createDirectProcurement([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'receipt_date' => now()->toDateString(),
            'notes' => 'Pengadaan campuran',
        ], array_map(fn (Item $item): array => [
            'item_id' => $item->id,
            'unit_id' => $unit->id,
            'purchase_unit_id' => $unit->id,
            'purchase_qty' => 2,
            'conversion_qty' => 1,
            'line_total' => 2000,
            'notes' => 'Item masuk',
        ], $items), $user);

        $this->assertSame(4, $receipt->items()->count());
        $this->assertEqualsWithDelta(8000, (float) FinanceExpense::query()->whereMorphedTo('reference', $receipt)->firstOrFail()->amount, 0.01);

        foreach ($items as $item) {
            $this->assertDatabaseHas('stock_balances', [
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'qty_on_hand' => 2,
            ]);
        }
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

    /**
     * @return array{0: Unit, 1: Unit, 2: Warehouse, 3: Item, 4: Supplier, 5: ItemStage}
     */
    protected function basicProcurementFixture(): array
    {
        $gram = Unit::query()->updateOrCreate(['code' => 'GR'], [
            'name' => 'Gram',
            'is_base' => true,
            'precision' => 4,
            'is_active' => true,
        ]);
        $kg = Unit::query()->updateOrCreate(['code' => 'KG'], [
            'name' => 'Kilogram',
            'is_base' => false,
            'precision' => 4,
            'is_active' => true,
        ]);
        $category = ItemCategory::query()->updateOrCreate(['slug' => 'raw-material'], [
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
            'sku' => 'RM-KENCUR-SPRINT',
            'name' => 'Kencur Sprint',
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
            'code' => 'SUP-SPRINT',
            'name' => 'Supplier Sprint',
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-SPRINT',
            'name' => 'Warehouse Sprint',
            'location_type' => 'central',
            'is_active' => true,
        ]);

        return [$gram, $kg, $warehouse, $item, $supplier, $stage];
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
