<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $premixCategoryId = DB::table('item_categories')
            ->where('slug', 'premix')
            ->value('id');

        if (! $premixCategoryId) {
            return;
        }

        DB::table('items')
            ->where('item_category_id', $premixCategoryId)
            ->update([
                'item_type' => 'premix',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $premixCategoryId = DB::table('item_categories')
            ->where('slug', 'premix')
            ->value('id');

        if (! $premixCategoryId) {
            return;
        }

        DB::table('items')
            ->where('item_category_id', $premixCategoryId)
            ->update([
                'item_type' => 'material',
                'updated_at' => now(),
            ]);
    }
};
