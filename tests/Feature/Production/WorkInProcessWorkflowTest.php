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
use App\Services\WorkInProcessService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkInProcessWorkflowTest extends TestCase
{
    public function test_work_in_process_can_consume_srm_stage_stock(): void
    {
        $this->prepareDatabase();

        $user = User::factory()->create(['is_active' => true]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-WIP-SRM',
            'name' => 'Warehouse WIP SRM',
            'location_type' => 'central',
            'is_active' => true,
        ]);

        $ball = Unit::query()->firstOrCreate(['code' => 'BALL'], ['name' => 'Ball', 'precision' => 4, 'is_active' => true]);
        $pcs = Unit::query()->firstOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'precision' => 0, 'is_active' => true]);
        $srmStage = ItemStage::query()->updateOrCreate(['code' => ItemStageCode::Srm->value], ['name' => 'SRM', 'sequence' => 1, 'is_active' => true]);
        $fgStage = ItemStage::query()->updateOrCreate(['code' => ItemStageCode::FinishedGoods->value], ['name' => 'Finished Goods', 'sequence' => 2, 'is_active' => true]);
        ItemStage::query()->updateOrCreate(['code' => ItemStageCode::Wip->value], ['name' => 'WIP', 'sequence' => 3, 'is_active' => true]);

        $srmCategory = ItemCategory::query()->firstOrCreate(['slug' => 'srm'], ['name' => 'SRM', 'category_type' => 'wip', 'is_active' => true]);
        $fgCategory = ItemCategory::query()->firstOrCreate(['slug' => 'finished-goods'], ['name' => 'Finished Goods', 'category_type' => 'finished_goods', 'is_active' => true]);

        $inputItem = Item::query()->create([
            'sku' => 'SRM-WIP-TEST',
            'name' => 'SRM WIP Test',
            'item_category_id' => $srmCategory->id,
            'default_unit_id' => $ball->id,
            'default_stage_id' => $srmStage->id,
            'item_type' => 'semi_finished',
            'requires_production' => true,
            'minimum_stock' => 0,
            'latest_weighted_avg_cost' => 50000,
            'is_active' => true,
        ]);

        $outputItem = Item::query()->create([
            'sku' => 'FG-WIP-TEST',
            'name' => 'FG WIP Test',
            'item_category_id' => $fgCategory->id,
            'default_unit_id' => $pcs->id,
            'default_stage_id' => $fgStage->id,
            'item_type' => 'product',
            'requires_production' => true,
            'minimum_stock' => 0,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);

        StockBalance::query()->create([
            'balance_key' => "{$inputItem->id}:{$srmStage->id}:{$warehouse->id}:0:0",
            'item_id' => $inputItem->id,
            'stage_id' => $srmStage->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 5,
            'avg_cost' => 50000,
            'total_value' => 250000,
        ]);

        $process = app(WorkInProcessService::class)->post([
            'process_date' => now()->toDateString(),
            'process_type' => 'internal',
            'warehouse_id' => $warehouse->id,
            'input_item_id' => $inputItem->id,
            'output_item_id' => $outputItem->id,
            'input_unit_id' => $ball->id,
            'output_unit_id' => $pcs->id,
            'input_qty' => 2,
            'standard_conversion_per_unit' => 10,
            'actual_output_qty' => 18,
            'overhead_cost' => 4000,
        ], $user);

        $this->assertSame($inputItem->id, $process->input_item_id);

        $srmBalance = StockBalance::query()
            ->where('item_id', $inputItem->id)
            ->where('stage_id', $srmStage->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        $fgBalance = StockBalance::query()
            ->where('item_id', $outputItem->id)
            ->where('stage_id', $fgStage->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        $this->assertSame(3.0, (float) $srmBalance->qty_on_hand);
        $this->assertSame(18.0, (float) $fgBalance->qty_on_hand);
        $this->assertEqualsWithDelta(5777.7778, (float) $fgBalance->avg_cost, 0.0001);
    }

    public function test_work_in_process_rejects_non_srm_input(): void
    {
        $this->prepareDatabase();

        $user = User::factory()->create(['is_active' => true]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-WIP-RM',
            'name' => 'Warehouse WIP RM',
            'location_type' => 'central',
            'is_active' => true,
        ]);
        $unit = Unit::query()->firstOrCreate(['code' => 'KG'], ['name' => 'Kilogram', 'precision' => 4, 'is_active' => true]);
        $rmStage = ItemStage::query()->updateOrCreate(['code' => ItemStageCode::RawDirty->value], ['name' => 'Raw Dirty', 'sequence' => 1, 'is_active' => true]);
        $fgStage = ItemStage::query()->updateOrCreate(['code' => ItemStageCode::FinishedGoods->value], ['name' => 'Finished Goods', 'sequence' => 2, 'is_active' => true]);
        $rmCategory = ItemCategory::query()->firstOrCreate(['slug' => 'raw-material'], ['name' => 'Raw Material', 'category_type' => 'raw_material', 'is_active' => true]);
        $fgCategory = ItemCategory::query()->firstOrCreate(['slug' => 'finished-goods'], ['name' => 'Finished Goods', 'category_type' => 'finished_goods', 'is_active' => true]);

        $inputItem = Item::query()->create([
            'sku' => 'RM-WIP-REJECT',
            'name' => 'RM Reject',
            'item_category_id' => $rmCategory->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $rmStage->id,
            'item_type' => 'material',
            'requires_production' => false,
            'minimum_stock' => 0,
            'latest_weighted_avg_cost' => 50000,
            'is_active' => true,
        ]);
        $outputItem = Item::query()->create([
            'sku' => 'FG-WIP-REJECT',
            'name' => 'FG Reject',
            'item_category_id' => $fgCategory->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $fgStage->id,
            'item_type' => 'product',
            'requires_production' => true,
            'minimum_stock' => 0,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Input produksi harus barang kategori SRM.');

        app(WorkInProcessService::class)->post([
            'warehouse_id' => $warehouse->id,
            'input_item_id' => $inputItem->id,
            'output_item_id' => $outputItem->id,
            'input_unit_id' => $unit->id,
            'output_unit_id' => $unit->id,
            'input_qty' => 1,
            'standard_conversion_per_unit' => 1,
            'actual_output_qty' => 1,
        ], $user);
    }

    protected function prepareDatabase(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ekstensi pdo_sqlite belum tersedia pada environment ini.');
        }

        $this->artisan('migrate:fresh');
    }
}
