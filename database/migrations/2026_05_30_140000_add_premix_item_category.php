<?php

use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $premixCategory = ItemCategory::query()->updateOrCreate(
            ['slug' => 'premix'],
            [
                'name' => 'Premix',
                'category_type' => 'wip',
                'is_active' => true,
            ],
        );

        Item::query()
            ->where(function ($query): void {
                $query->where('sku', 'like', '%PREMIX%')
                    ->orWhere('name', 'like', '%Premix%');
            })
            ->update(['item_category_id' => $premixCategory->id]);
    }

    public function down(): void
    {
        $wipCategoryId = ItemCategory::query()->where('slug', 'wip')->value('id');
        $premixCategory = ItemCategory::query()->where('slug', 'premix')->first();

        if ($wipCategoryId && $premixCategory) {
            Item::query()
                ->where('item_category_id', $premixCategory->id)
                ->update(['item_category_id' => $wipCategoryId]);
        }

        $premixCategory?->delete();
    }
};
