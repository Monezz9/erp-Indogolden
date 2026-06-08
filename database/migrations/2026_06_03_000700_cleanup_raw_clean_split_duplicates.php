<?php

use App\Enums\ItemStageCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rawDirtyStageId = DB::table('item_stages')->where('code', ItemStageCode::RawDirty->value)->value('id');
        $rawCleanStageId = DB::table('item_stages')->where('code', ItemStageCode::RawClean->value)->value('id');
        $rawCleanCategoryId = DB::table('item_categories')->where('slug', 'raw-clean')->value('id');

        if (! $rawDirtyStageId || ! $rawCleanStageId || ! $rawCleanCategoryId) {
            return;
        }

        DB::table('items')
            ->where('default_stage_id', $rawDirtyStageId)
            ->where('sku', 'like', 'RC-%')
            ->orderBy('id')
            ->get(['id', 'sku'])
            ->each(function (object $item) use ($now): void {
                $rmSku = 'RM-'.substr((string) $item->sku, 3);

                if (DB::table('items')->where('sku', $rmSku)->exists()) {
                    return;
                }

                DB::table('items')
                    ->where('id', $item->id)
                    ->update([
                        'sku' => $rmSku,
                        'updated_at' => $now,
                    ]);
            });

        DB::table('items')
            ->where('default_stage_id', $rawCleanStageId)
            ->where('sku', 'like', 'RC-RC-%')
            ->orderBy('id')
            ->get(['id', 'sku', 'name'])
            ->each(function (object $duplicate) use ($rawCleanStageId, $rawCleanCategoryId, $now): void {
                $baseName = preg_replace('/\s+Bersih$/', '', (string) $duplicate->name) ?: (string) $duplicate->name;
                $target = DB::table('items')
                    ->where('id', '!=', $duplicate->id)
                    ->where('default_stage_id', $rawCleanStageId)
                    ->where('item_category_id', $rawCleanCategoryId)
                    ->where('name', $baseName)
                    ->first(['id']);

                if (! $target) {
                    $cleanSku = 'RC-'.substr((string) $duplicate->sku, 6);

                    if (! DB::table('items')->where('sku', $cleanSku)->exists()) {
                        DB::table('items')
                            ->where('id', $duplicate->id)
                            ->update([
                                'sku' => $cleanSku,
                                'updated_at' => $now,
                            ]);
                    }

                    return;
                }

                DB::table('stock_balances')
                    ->where('item_id', $duplicate->id)
                    ->get()
                    ->each(function (object $balance) use ($target, $now): void {
                        $newBalanceKey = implode(':', [
                            $target->id,
                            $balance->stage_id,
                            $balance->warehouse_id ?? 0,
                            $balance->branch_id ?? 0,
                            $balance->stock_batch_id ?? 0,
                        ]);

                        DB::table('stock_balances')->updateOrInsert(
                            ['balance_key' => $newBalanceKey],
                            [
                                'item_id' => $target->id,
                                'stage_id' => $balance->stage_id,
                                'warehouse_id' => $balance->warehouse_id,
                                'branch_id' => $balance->branch_id,
                                'stock_batch_id' => $balance->stock_batch_id,
                                'qty_on_hand' => $balance->qty_on_hand,
                                'avg_cost' => $balance->avg_cost,
                                'total_value' => $balance->total_value,
                                'last_movement_item_id' => $balance->last_movement_item_id,
                                'last_updated_at' => $balance->last_updated_at,
                                'updated_at' => $now,
                                'created_at' => $balance->created_at ?? $now,
                            ],
                        );
                    });

                DB::table('cleaning_processes')
                    ->where('output_item_id', $duplicate->id)
                    ->update([
                        'output_item_id' => $target->id,
                        'updated_at' => $now,
                    ]);

                DB::table('stock_balances')->where('item_id', $duplicate->id)->delete();
                DB::table('items')->where('id', $duplicate->id)->delete();
            });
    }

    public function down(): void
    {
        // Data cleanup is intentionally not reversed.
    }
};
