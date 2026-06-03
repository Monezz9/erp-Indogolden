<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('item_categories')->updateOrInsert(
            ['slug' => 'raw-clean'],
            [
                'name' => 'Raw Clean',
                'category_type' => 'raw_material',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        $rawCleanCategoryId = DB::table('item_categories')->where('slug', 'raw-clean')->value('id');
        $rawMaterialCategoryId = DB::table('item_categories')->where('slug', 'raw-material')->value('id');
        $rawCleanStageId = DB::table('item_stages')->where('code', 'raw_clean')->value('id');

        if (! $rawCleanCategoryId || ! $rawMaterialCategoryId || ! $rawCleanStageId) {
            return;
        }

        DB::table('items')
            ->where('item_category_id', $rawMaterialCategoryId)
            ->where('default_stage_id', $rawCleanStageId)
            ->update([
                'item_category_id' => $rawCleanCategoryId,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        $rawCleanCategoryId = DB::table('item_categories')->where('slug', 'raw-clean')->value('id');
        $rawMaterialCategoryId = DB::table('item_categories')->where('slug', 'raw-material')->value('id');

        if ($rawCleanCategoryId && $rawMaterialCategoryId) {
            DB::table('items')
                ->where('item_category_id', $rawCleanCategoryId)
                ->update([
                    'item_category_id' => $rawMaterialCategoryId,
                    'updated_at' => now(),
                ]);
        }

        DB::table('item_categories')->where('slug', 'raw-clean')->delete();
    }
};
