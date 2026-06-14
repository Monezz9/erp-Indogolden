<?php

namespace Tests\Feature\Inventory;

use App\Enums\ItemStageCode;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\ProductionRecipe;
use App\Models\Unit;
use App\Models\User;
use App\Services\CleaningProcessService;
use App\Services\ProductionService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CategorySimplificationTest extends TestCase
{
    public function test_existing_rc_and_premix_categories_are_mapped_to_srm(): void
    {
        $this->prepareDatabase();

        $unit = Unit::query()->updateOrCreate(['code' => 'GR'], ['name' => 'Gram', 'precision' => 2, 'is_active' => true]);
        $rawCleanStage = ItemStage::query()->updateOrCreate(['code' => ItemStageCode::RawClean->value], ['name' => 'Raw Clean', 'sequence' => 1, 'is_active' => true]);
        ItemStage::query()->updateOrCreate(['code' => ItemStageCode::Srm->value], ['name' => 'SRM', 'sequence' => 2, 'is_active' => true]);
        $rawClean = ItemCategory::query()->updateOrCreate(['slug' => 'raw-clean'], ['name' => 'Raw Clean', 'category_type' => 'raw_material', 'is_active' => true]);
        $premix = ItemCategory::query()->updateOrCreate(['slug' => 'premix'], ['name' => 'Premix', 'category_type' => 'wip', 'is_active' => true]);

        $rcItem = $this->createItem('RC-LEGACY', 'Legacy RC', $rawClean, $unit, $rawCleanStage);
        $premixItem = $this->createItem('PREMIX-LEGACY', 'Legacy Premix', $premix, $unit, $rawCleanStage, 'premix');

        (require database_path('migrations/2026_06_14_000200_simplify_item_categories_to_rm_srm_fg.php'))->up();

        $rcItem->refresh()->load('category', 'defaultStage');
        $premixItem->refresh()->load('category', 'defaultStage');

        $this->assertSame('srm', $rcItem->category->slug);
        $this->assertSame('srm', $premixItem->category->slug);
        $this->assertSame(ItemStageCode::Srm->value, $rcItem->defaultStage->code);
        $this->assertSame(ItemStageCode::Srm->value, $premixItem->defaultStage->code);
        $this->assertFalse((bool) ItemCategory::query()->where('slug', 'raw-clean')->value('is_active'));
        $this->assertFalse((bool) ItemCategory::query()->where('slug', 'premix')->value('is_active'));
    }

    public function test_item_filters_only_expose_rm_srm_fg_tabs(): void
    {
        $page = app(ListItems::class);

        $this->assertSame(['all', 'rm', 'srm', 'fg'], array_keys($page->getTabs()));
    }

    public function test_production_recipe_rejects_non_srm_inputs(): void
    {
        $this->prepareDatabase();

        $unit = Unit::query()->updateOrCreate(['code' => 'GR'], ['name' => 'Gram', 'precision' => 2, 'is_active' => true]);
        $rmStage = ItemStage::query()->updateOrCreate(['code' => ItemStageCode::RawDirty->value], ['name' => 'Raw Dirty', 'sequence' => 1, 'is_active' => true]);
        $fgStage = ItemStage::query()->updateOrCreate(['code' => ItemStageCode::FinishedGoods->value], ['name' => 'FG', 'sequence' => 2, 'is_active' => true]);
        $rmCategory = ItemCategory::query()->updateOrCreate(['slug' => 'raw-material'], ['name' => 'Raw Material', 'category_type' => 'raw_material', 'is_active' => true]);
        $fgCategory = ItemCategory::query()->updateOrCreate(['slug' => 'finished-goods'], ['name' => 'FG', 'category_type' => 'finished_goods', 'is_active' => true]);
        $rm = $this->createItem('RM-INPUT', 'RM Input', $rmCategory, $unit, $rmStage);
        $fg = $this->createItem('FG-OUTPUT', 'FG Output', $fgCategory, $unit, $fgStage, 'product');

        $recipe = ProductionRecipe::query()->create([
            'code' => 'RC-NON-SRM',
            'name' => 'Non SRM Recipe',
            'output_item_id' => $fg->id,
            'output_unit_id' => $unit->id,
            'output_qty' => 1,
            'yield_percentage' => 100,
            'is_active' => true,
        ]);
        $recipe->ingredients()->create([
            'item_id' => $rm->id,
            'unit_id' => $unit->id,
            'stage_id' => $rmStage->id,
            'qty' => 1,
            'is_optional' => false,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Semua input produksi harus barang kategori SRM.');

        app(ProductionService::class)->createOrder($recipe, 1, User::factory()->create(['is_active' => true]));
    }

    public function test_grooming_auto_output_remains_srm(): void
    {
        $this->prepareDatabase();

        $user = User::factory()->create(['is_active' => true]);
        $unit = Unit::query()->updateOrCreate(['code' => 'GR'], ['name' => 'Gram', 'precision' => 2, 'is_active' => true]);
        $rawDirty = ItemStage::query()->updateOrCreate(['code' => ItemStageCode::RawDirty->value], ['name' => 'Raw Dirty', 'sequence' => 1, 'is_active' => true]);
        ItemStage::query()->updateOrCreate(['code' => ItemStageCode::Srm->value], ['name' => 'SRM', 'sequence' => 2, 'is_active' => true]);
        $warehouse = \App\Models\Warehouse::query()->create(['code' => 'WH-GROOM', 'name' => 'Grooming', 'location_type' => 'central', 'is_active' => true]);
        $rmCategory = ItemCategory::query()->updateOrCreate(['slug' => 'raw-material'], ['name' => 'Raw Material', 'category_type' => 'raw_material', 'is_active' => true]);
        $srmCategory = ItemCategory::query()->updateOrCreate(['slug' => 'srm'], ['name' => 'SRM', 'category_type' => 'wip', 'is_active' => true]);
        $rm = $this->createItem('RM-KENCUR', 'Kencur', $rmCategory, $unit, $rawDirty);
        $srm = $this->createItem('SRM-KENCUR', 'Kencur', $srmCategory, $unit, ItemStage::query()->where('code', ItemStageCode::Srm->value)->firstOrFail(), 'semi_finished');

        \App\Models\StockBalance::query()->create([
            'balance_key' => "{$rm->id}:{$rawDirty->id}:{$warehouse->id}:0:0",
            'item_id' => $rm->id,
            'stage_id' => $rawDirty->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 1000,
            'avg_cost' => 30,
            'total_value' => 30000,
        ]);

        $process = app(CleaningProcessService::class)->start([
            'warehouse_id' => $warehouse->id,
            'item_id' => $rm->id,
            'unit_id' => $unit->id,
            'input_qty' => 100,
            'notes' => 'Mulai grooming',
        ], $user);

        $this->assertSame($srm->id, $process->output_item_id);
        $this->assertSame('srm', $process->outputItem->category->slug);
    }

    protected function createItem(string $sku, string $name, ItemCategory $category, Unit $unit, ItemStage $stage, string $type = 'material'): Item
    {
        return Item::query()->create([
            'sku' => $sku,
            'name' => $name,
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $stage->id,
            'item_type' => $type,
            'requires_production' => in_array($type, ['semi_finished', 'product'], true),
            'is_perishable' => false,
            'minimum_stock' => 0,
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
