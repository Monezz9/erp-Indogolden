<?php

use App\Enums\ItemStageCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $rawCategoryId = $this->upsertAndGetId('item_categories', 'slug', 'raw-material', [
            'name' => 'Raw Material',
            'category_type' => 'raw_material',
            'is_active' => true,
            'updated_at' => $now,
        ]);

        $finishedGoodsCategoryId = $this->upsertAndGetId('item_categories', 'slug', 'finished-goods', [
            'name' => 'Finished Goods',
            'category_type' => 'finished_goods',
            'is_active' => true,
            'updated_at' => $now,
        ]);

        $mroCategoryId = $this->upsertAndGetId('item_categories', 'slug', 'mro', [
            'name' => 'MRO',
            'category_type' => 'mro',
            'is_active' => true,
            'updated_at' => $now,
        ]);

        $gramUnitId = $this->upsertAndGetId('units', 'code', 'GR', [
            'name' => 'Gram',
            'precision' => 2,
            'is_base' => false,
            'is_active' => true,
            'updated_at' => $now,
        ]);

        $pcsUnitId = $this->upsertAndGetId('units', 'code', 'PCS', [
            'name' => 'Pieces',
            'precision' => 0,
            'is_base' => true,
            'is_active' => true,
            'updated_at' => $now,
        ]);

        $rawCleanStageId = $this->stageId(ItemStageCode::RawClean->value, 'Raw Clean', 2);
        $finishedGoodsStageId = $this->stageId(ItemStageCode::FinishedGoods->value, 'Finished Goods', 4);
        $mroStageId = $this->stageId(ItemStageCode::Mro->value, 'MRO', 6);

        $this->upsertItem('RM-PENYEDAP', 'Penyedap', $rawCategoryId, $gramUnitId, $rawCleanStageId, 'material');
        $this->upsertItem('RM-MICIN', 'Micin', $rawCategoryId, $gramUnitId, $rawCleanStageId, 'material');
        $this->upsertItem('RM-GARAM', 'Garam', $rawCategoryId, $gramUnitId, $rawCleanStageId, 'material');
        $this->upsertItem('RM-LADA', 'Lada', $rawCategoryId, $gramUnitId, $rawCleanStageId, 'material');
        $this->upsertItem('RM-GULA', 'Gula', $rawCategoryId, $gramUnitId, $rawCleanStageId, 'material');
        $this->upsertItem('MRO-PLASTIK-PREMIX', 'Plastik Premix', $mroCategoryId, $pcsUnitId, $mroStageId, 'packaging');
        $premixId = $this->upsertItem('FG-PREMIX', 'Premix', $finishedGoodsCategoryId, $pcsUnitId, $finishedGoodsStageId, 'product', true);

        $recipeId = $this->upsertAndGetId('production_recipes', 'code', 'RC-FG-PREMIX', [
            'name' => 'Premix Standard',
            'output_item_id' => $premixId,
            'output_unit_id' => $pcsUnitId,
            'output_qty' => 1,
            'yield_percentage' => 100,
            'notes' => 'Resep spreadsheet 2/6: bahan gram menjadi 1 PCS Premix.',
            'is_active' => true,
            'updated_at' => $now,
        ]);

        DB::table('production_recipe_items')->where('production_recipe_id', $recipeId)->delete();

        foreach ([
            ['sku' => 'RM-PENYEDAP', 'unit_id' => $gramUnitId, 'stage_id' => $rawCleanStageId, 'qty' => 80],
            ['sku' => 'RM-MICIN', 'unit_id' => $gramUnitId, 'stage_id' => $rawCleanStageId, 'qty' => 22],
            ['sku' => 'RM-GARAM', 'unit_id' => $gramUnitId, 'stage_id' => $rawCleanStageId, 'qty' => 18],
            ['sku' => 'RM-LADA', 'unit_id' => $gramUnitId, 'stage_id' => $rawCleanStageId, 'qty' => 10],
            ['sku' => 'RM-GULA', 'unit_id' => $gramUnitId, 'stage_id' => $rawCleanStageId, 'qty' => 26],
            ['sku' => 'MRO-PLASTIK-PREMIX', 'unit_id' => $pcsUnitId, 'stage_id' => $mroStageId, 'qty' => 1],
        ] as $line) {
            DB::table('production_recipe_items')->insert([
                'production_recipe_id' => $recipeId,
                'item_id' => DB::table('items')->where('sku', $line['sku'])->value('id'),
                'unit_id' => $line['unit_id'],
                'stage_id' => $line['stage_id'],
                'qty' => $line['qty'],
                'is_optional' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Keep operational master data intact on rollback.
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function upsertAndGetId(string $table, string $key, string $value, array $values): int
    {
        DB::table($table)->updateOrInsert(
            [$key => $value],
            $values + ['created_at' => now()],
        );

        return (int) DB::table($table)->where($key, $value)->value('id');
    }

    private function stageId(string $code, string $name, int $sequence): int
    {
        return $this->upsertAndGetId('item_stages', 'code', $code, [
            'name' => $name,
            'sequence' => $sequence,
            'is_active' => true,
            'updated_at' => now(),
        ]);
    }

    private function upsertItem(
        string $sku,
        string $name,
        int $categoryId,
        int $unitId,
        int $stageId,
        string $type,
        bool $requiresProduction = false,
    ): int {
        DB::table('items')->updateOrInsert(
            ['sku' => $sku],
            [
                'name' => $name,
                'item_category_id' => $categoryId,
                'default_unit_id' => $unitId,
                'default_stage_id' => $stageId,
                'item_type' => $type,
                'requires_production' => $requiresProduction,
                'is_perishable' => false,
                'minimum_stock' => 0,
                'is_active' => true,
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('items')->where('sku', $sku)->value('id');
    }
};
