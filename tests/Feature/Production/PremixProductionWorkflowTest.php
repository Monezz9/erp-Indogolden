<?php

namespace Tests\Feature\Production;

use App\Enums\ItemStageCode;
use App\Enums\ProductionOrderStatus;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\ProductionRecipe;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ProductionService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PremixProductionWorkflowTest extends TestCase
{
    public function test_premix_recipe_consumes_gram_inputs_and_outputs_pcs_with_calculated_hpp(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole();
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-PREMIX',
            'name' => 'Warehouse Premix',
            'location_type' => 'production',
            'is_active' => true,
        ]);

        [$srmStage, $fgStage] = $this->createStages();
        [$gram, $pcs] = $this->createUnits();
        [$srmCategory, $fgCategory] = $this->createCategories();

        $ingredients = [
            'PENYEDAP' => $this->createItem('SRM-PENYEDAP-T', 'Penyedap', $srmCategory, $gram, $srmStage, 'semi_finished', 89, 80),
            'MICIN' => $this->createItem('SRM-MICIN-T', 'Micin', $srmCategory, $gram, $srmStage, 'semi_finished', 52, 22),
            'GARAM' => $this->createItem('SRM-GARAM-T', 'Garam', $srmCategory, $gram, $srmStage, 'semi_finished', 14, 18),
            'LADA' => $this->createItem('SRM-LADA-T', 'Lada', $srmCategory, $gram, $srmStage, 'semi_finished', 170, 10),
            'GULA' => $this->createItem('SRM-GULA-T', 'Gula', $srmCategory, $gram, $srmStage, 'semi_finished', 16.7, 26),
        ];

        foreach ($ingredients as $ingredient) {
            $this->seedBalance($ingredient['item'], $ingredient['stage'], $warehouse, 10000, $ingredient['cost']);
        }

        $premix = $this->createItem('FG-PREMIX-T', 'Premix', $fgCategory, $pcs, $fgStage, 'product', 0, 1)['item'];

        $recipe = ProductionRecipe::query()->create([
            'code' => 'RC-PREMIX-T',
            'name' => 'Premix Test',
            'output_item_id' => $premix->id,
            'output_unit_id' => $pcs->id,
            'output_qty' => 1,
            'yield_percentage' => 100,
            'is_active' => true,
        ]);

        foreach ($ingredients as $ingredient) {
            $recipe->ingredients()->create([
                'item_id' => $ingredient['item']->id,
                'unit_id' => $ingredient['unit']->id,
                'stage_id' => $ingredient['stage']->id,
                'qty' => $ingredient['recipe_qty'],
                'is_optional' => false,
            ]);
        }

        $service = app(ProductionService::class);
        $order = $service->createOrder($recipe, 10, $user);
        $order = $service->submitOrder($order, $user);
        $order = $service->completeOrder($order, $user, $warehouse->id);

        $this->assertSame(ProductionOrderStatus::Completed, $order->status);
        $this->assertSame(10.0, (float) $order->actual_qty);
        $this->assertSame(106502.0, round((float) $order->total_input_cost, 2));
        $this->assertSame(10650.2, round((float) $order->outputs->first()->unit_cost, 2));

        $fgBalance = StockBalance::query()
            ->where('item_id', $premix->id)
            ->where('stage_id', $fgStage->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();

        $this->assertSame(10.0, (float) $fgBalance->qty_on_hand);
        $this->assertSame(10650.2, round((float) $fgBalance->avg_cost, 2));
    }

    protected function createUserWithRole(): User
    {
        Role::findOrCreate('produksi', 'web');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('produksi');

        return $user;
    }

    protected function prepareDatabase(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ekstensi pdo_sqlite belum tersedia pada environment ini.');
        }

        $this->artisan('migrate:fresh');
    }

    /**
     * @return array{ItemStage, ItemStage}
     */
    protected function createStages(): array
    {
        return [
            ItemStage::query()->updateOrCreate(['code' => ItemStageCode::Srm->value], ['name' => 'SRM', 'sequence' => 1, 'is_active' => true]),
            ItemStage::query()->updateOrCreate(['code' => ItemStageCode::FinishedGoods->value], ['name' => 'Finished Goods', 'sequence' => 2, 'is_active' => true]),
        ];
    }

    /**
     * @return array{Unit, Unit}
     */
    protected function createUnits(): array
    {
        return [
            Unit::query()->updateOrCreate(['code' => 'GR'], ['name' => 'Gram', 'precision' => 2, 'is_active' => true]),
            Unit::query()->updateOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'precision' => 0, 'is_base' => true, 'is_active' => true]),
        ];
    }

    /**
     * @return array{ItemCategory, ItemCategory}
     */
    protected function createCategories(): array
    {
        return [
            ItemCategory::query()->updateOrCreate(['slug' => 'srm'], ['name' => 'SRM', 'category_type' => 'wip', 'is_active' => true]),
            ItemCategory::query()->updateOrCreate(['slug' => 'fg-test'], ['name' => 'FG Test', 'category_type' => 'finished_goods', 'is_active' => true]),
        ];
    }

    /**
     * @return array{item: Item, unit: Unit, stage: ItemStage, cost: float, recipe_qty: float}
     */
    protected function createItem(
        string $sku,
        string $name,
        ItemCategory $category,
        Unit $unit,
        ItemStage $stage,
        string $type,
        float $cost,
        float $recipeQty,
    ): array {
        $item = Item::query()->create([
            'sku' => $sku,
            'name' => $name,
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $stage->id,
            'item_type' => $type,
            'requires_production' => $type === 'product',
            'is_perishable' => false,
            'minimum_stock' => 0,
            'purchase_price' => $cost,
            'latest_weighted_avg_cost' => $cost,
            'is_active' => true,
        ]);

        return [
            'item' => $item,
            'unit' => $unit,
            'stage' => $stage,
            'cost' => $cost,
            'recipe_qty' => $recipeQty,
        ];
    }

    protected function seedBalance(Item $item, ItemStage $stage, Warehouse $warehouse, float $qty, float $avgCost): void
    {
        StockBalance::query()->create([
            'balance_key' => implode(':', [$item->id, $stage->id, $warehouse->id, 0, 0]),
            'item_id' => $item->id,
            'stage_id' => $stage->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => $qty,
            'avg_cost' => $avgCost,
            'total_value' => $qty * $avgCost,
        ]);
    }
}
