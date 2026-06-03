<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('item_categories')->updateOrInsert(
            ['slug' => 'premix'],
            [
                'name' => 'Premix',
                'category_type' => 'wip',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $premixCategoryId = DB::table('item_categories')
            ->where('slug', 'premix')
            ->value('id');

        DB::table('items')
            ->whereIn('sku', ['RM-PENYEDAP', 'RM-MICIN', 'RM-GARAM', 'RM-LADA', 'RM-GULA'])
            ->update([
                'item_category_id' => $premixCategoryId,
                'item_type' => 'premix',
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        $rawMaterialCategoryId = DB::table('item_categories')
            ->where('slug', 'raw-material')
            ->value('id');

        if (! $rawMaterialCategoryId) {
            return;
        }

        DB::table('items')
            ->whereIn('sku', ['RM-PENYEDAP', 'RM-MICIN', 'RM-GARAM', 'RM-LADA', 'RM-GULA'])
            ->update([
                'item_category_id' => $rawMaterialCategoryId,
                'item_type' => 'material',
                'updated_at' => now(),
            ]);
    }
};
