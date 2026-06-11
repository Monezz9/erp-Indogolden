<?php

namespace Tests\Feature\Inventory;

use App\Enums\ItemStageCode;
use App\Filament\Resources\Items\Tables\ItemsTable;
use App\Filament\Resources\Items\Widgets\ItemInventoryOverview;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\InventoryStockStatus;
use Tests\TestCase;

class InventoryStatusRegressionTest extends TestCase
{
    public function test_stock_status_and_critical_kpi_use_same_definition(): void
    {
        $this->prepareDatabase();

        $unit = Unit::query()->firstOrCreate(['code' => 'PCS'], [
            'code' => 'PCS',
            'name' => 'Pieces',
            'is_base' => true,
            'precision' => 0,
            'is_active' => true,
        ]);
        $category = ItemCategory::query()->firstOrCreate(['slug' => 'finished-goods-test'], [
            'name' => 'Finished Goods',
            'slug' => 'finished-goods-test',
            'category_type' => 'finished_goods',
            'is_active' => true,
        ]);
        $stage = ItemStage::query()->firstOrCreate(['code' => ItemStageCode::FinishedGoods->value], [
            'code' => ItemStageCode::FinishedGoods->value,
            'name' => 'Finished Goods',
            'sequence' => 1,
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->firstOrCreate(['code' => 'WH-INV'], [
            'code' => 'WH-INV',
            'name' => 'Warehouse Inventory',
            'location_type' => 'central',
            'is_active' => true,
        ]);

        $criticalItem = Item::query()->create([
            'sku' => 'FG-CRITICAL-75',
            'name' => 'Item Kritis 75',
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $stage->id,
            'item_type' => 'product',
            'requires_production' => false,
            'is_perishable' => false,
            'minimum_stock' => 100,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);
        $safeItem = Item::query()->create([
            'sku' => 'FG-SAFE-150',
            'name' => 'Item Aman 150',
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $stage->id,
            'item_type' => 'product',
            'requires_production' => false,
            'is_perishable' => false,
            'minimum_stock' => 100,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);

        $this->seedBalance($criticalItem, $stage, $warehouse, 75);
        $this->seedBalance($safeItem, $stage, $warehouse, 150);

        $criticalRecord = Item::query()
            ->with('defaultUnit')
            ->withSum('stockBalances as stock_qty', 'qty_on_hand')
            ->findOrFail($criticalItem->id);

        $this->assertSame(InventoryStockStatus::CRITICAL, ItemsTable::stockStatus($criticalRecord));

        $cards = (new ItemInventoryOverview())->inventorySummaryCards();
        $criticalCard = collect($cards)->firstWhere('label', 'Stok Kritis');

        $this->assertSame('1', $criticalCard['value']);
    }

    protected function seedBalance(Item $item, ItemStage $stage, Warehouse $warehouse, float $qty): void
    {
        StockBalance::query()->create([
            'balance_key' => implode(':', [$item->id, $stage->id, $warehouse->id, 0, 0]),
            'item_id' => $item->id,
            'stage_id' => $stage->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => $qty,
            'avg_cost' => 0,
            'total_value' => 0,
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
