<?php

use App\Enums\ItemStageCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('item_categories')->updateOrInsert(
            ['slug' => 'srm'],
            [
                'name' => 'SRM',
                'category_type' => 'wip',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $srmCategoryId = DB::table('item_categories')->where('slug', 'srm')->value('id');
        $legacyCategoryIds = DB::table('item_categories')
            ->whereIn('slug', ['raw-clean', 'premix'])
            ->pluck('id')
            ->all();
        $srmStageId = DB::table('item_stages')->where('code', ItemStageCode::Srm->value)->value('id');
        $rawCleanStageId = DB::table('item_stages')->where('code', ItemStageCode::RawClean->value)->value('id');
        $wipStageId = DB::table('item_stages')->where('code', ItemStageCode::Wip->value)->value('id');

        if ($srmCategoryId && $legacyCategoryIds) {
            DB::table('items')
                ->whereIn('item_category_id', $legacyCategoryIds)
                ->update([
                    'item_category_id' => $srmCategoryId,
                    'item_type' => 'semi_finished',
                    'requires_production' => true,
                    'updated_at' => $now,
                ]);
        }

        if ($srmStageId) {
            $srmItemIds = DB::table('items')
                ->where('item_category_id', $srmCategoryId)
                ->pluck('id')
                ->all();

            DB::table('items')
                ->where('item_category_id', $srmCategoryId)
                ->whereIn('default_stage_id', array_filter([$rawCleanStageId, $wipStageId]))
                ->update([
                    'default_stage_id' => $srmStageId,
                    'updated_at' => $now,
                ]);

            DB::table('production_recipe_items')
                ->whereIn('item_id', $srmItemIds)
                ->update([
                    'stage_id' => $srmStageId,
                    'updated_at' => $now,
                ]);

            DB::table('production_order_inputs')
                ->whereIn('item_id', $srmItemIds)
                ->update([
                    'stage_id' => $srmStageId,
                    'updated_at' => $now,
                ]);
        }

        DB::table('item_categories')
            ->whereIn('slug', ['raw-clean', 'premix'])
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        DB::table('item_categories')
            ->whereIn('slug', ['raw-clean', 'premix'])
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
