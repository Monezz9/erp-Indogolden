<?php

namespace Tests\Feature\Production;

use App\Enums\ItemStageCode;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CleaningProcessService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CleaningProcessWorkflowTest extends TestCase
{
    public function test_cleaning_process_moves_raw_dirty_to_raw_clean_and_records_shrinkage(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('cleaning@erp.test', 'gudang');
        $unit = Unit::query()->firstOrCreate(['code' => 'GR'], [
            'name' => 'Gram',
            'is_base' => true,
            'precision' => 4,
            'is_active' => true,
        ]);
        $category = ItemCategory::query()->create([
            'name' => 'Raw Material',
            'slug' => 'raw-material-cleaning',
            'category_type' => 'raw_material',
            'is_active' => true,
        ]);
        ItemCategory::query()->firstOrCreate(['slug' => 'raw-clean'], [
            'name' => 'Raw Clean',
            'category_type' => 'raw_material',
            'is_active' => true,
        ]);
        $rawDirty = ItemStage::query()->firstOrCreate(['code' => ItemStageCode::RawDirty->value], [
            'name' => 'Raw Dirty',
            'sequence' => 1,
            'is_active' => true,
        ]);
        $rawClean = ItemStage::query()->firstOrCreate(['code' => ItemStageCode::RawClean->value], [
            'name' => 'Raw Clean',
            'sequence' => 2,
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'RM-KENCUR-CLEAN',
            'name' => 'Kencur',
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $rawDirty->id,
            'item_type' => 'material',
            'requires_production' => false,
            'is_perishable' => false,
            'minimum_stock' => 0,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-CLEAN',
            'name' => 'Warehouse Cleaning',
            'location_type' => 'central',
            'is_active' => true,
        ]);

        StockBalance::query()->create([
            'balance_key' => "{$item->id}:{$rawDirty->id}:{$warehouse->id}:0:0",
            'item_id' => $item->id,
            'stage_id' => $rawDirty->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 3000,
            'avg_cost' => 40,
            'total_value' => 120000,
        ]);

        $process = app(CleaningProcessService::class)->post([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'unit_id' => $unit->id,
            'input_qty' => 2000,
            'output_qty' => 1500,
        ], $user);

        $this->assertSame(500.0, (float) $process->shrinkage_qty);
        $this->assertSame(25.0, (float) $process->shrinkage_percent);
        $this->assertSame(80000.0, (float) $process->total_input_cost);
        $this->assertEqualsWithDelta(53.3333, (float) $process->output_unit_cost, 0.0001);
        $this->assertNotNull($process->output_item_id);

        $dirtyBalance = StockBalance::query()
            ->where('item_id', $item->id)
            ->where('stage_id', $rawDirty->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        $outputItem = Item::query()->whereKey($process->output_item_id)->firstOrFail();

        $cleanBalance = StockBalance::query()
            ->where('item_id', $outputItem->id)
            ->where('stage_id', $rawClean->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        $this->assertSame('RC-KENCUR-CLEAN', $outputItem->sku);
        $this->assertSame(1000.0, (float) $dirtyBalance->qty_on_hand);
        $this->assertSame(1500.0, (float) $cleanBalance->qty_on_hand);
        $this->assertEqualsWithDelta(53.3333, (float) $cleanBalance->avg_cost, 0.0001);
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
