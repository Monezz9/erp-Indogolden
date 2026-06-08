<?php

use App\Enums\ItemStageCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaning_processes', function (Blueprint $table): void {
            if (! Schema::hasColumn('cleaning_processes', 'output_item_id')) {
                $table->foreignId('output_item_id')
                    ->nullable()
                    ->after('item_id')
                    ->constrained('items')
                    ->nullOnDelete();
            }
        });

        $now = now();
        $rawCleanCategoryId = DB::table('item_categories')->where('slug', 'raw-clean')->value('id');
        $rawCleanStageId = DB::table('item_stages')->where('code', ItemStageCode::RawClean->value)->value('id');
        $rawDirtyStageId = DB::table('item_stages')->where('code', ItemStageCode::RawDirty->value)->value('id');

        if (! $rawCleanCategoryId || ! $rawCleanStageId || ! $rawDirtyStageId) {
            return;
        }

        $rawCleanBalances = DB::table('stock_balances')
            ->join('items', 'items.id', '=', 'stock_balances.item_id')
            ->where('stock_balances.stage_id', $rawCleanStageId)
            ->where('items.default_stage_id', $rawDirtyStageId)
            ->where('stock_balances.qty_on_hand', '>', 0)
            ->select([
                'stock_balances.id as balance_id',
                'stock_balances.item_id',
                'stock_balances.warehouse_id',
                'stock_balances.branch_id',
                'stock_balances.stock_batch_id',
                'stock_balances.qty_on_hand',
                'stock_balances.avg_cost',
                'stock_balances.total_value',
                'stock_balances.last_movement_item_id',
                'stock_balances.last_updated_at',
                'items.sku',
                'items.name',
                'items.default_unit_id',
                'items.purchase_price',
                'items.selling_price',
            ])
            ->get();

        foreach ($rawCleanBalances as $balance) {
            $outputItemId = $this->rawCleanItemId(
                inputSku: (string) $balance->sku,
                inputName: (string) $balance->name,
                categoryId: (int) $rawCleanCategoryId,
                unitId: (int) $balance->default_unit_id,
                stageId: (int) $rawCleanStageId,
                purchasePrice: (float) $balance->purchase_price,
                sellingPrice: (float) $balance->selling_price,
                now: $now,
            );

            $newBalanceKey = implode(':', [
                $outputItemId,
                $rawCleanStageId,
                $balance->warehouse_id ?? 0,
                $balance->branch_id ?? 0,
                $balance->stock_batch_id ?? 0,
            ]);

            DB::table('stock_balances')->updateOrInsert(
                ['balance_key' => $newBalanceKey],
                [
                    'item_id' => $outputItemId,
                    'stage_id' => $rawCleanStageId,
                    'warehouse_id' => $balance->warehouse_id,
                    'branch_id' => $balance->branch_id,
                    'stock_batch_id' => $balance->stock_batch_id,
                    'qty_on_hand' => $balance->qty_on_hand,
                    'avg_cost' => $balance->avg_cost,
                    'total_value' => $balance->total_value,
                    'last_movement_item_id' => $balance->last_movement_item_id,
                    'last_updated_at' => $balance->last_updated_at,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            DB::table('stock_balances')->where('id', $balance->balance_id)->delete();

            DB::table('cleaning_processes')
                ->where('item_id', $balance->item_id)
                ->whereNull('output_item_id')
                ->update([
                    'output_item_id' => $outputItemId,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('cleaning_processes', function (Blueprint $table): void {
            if (Schema::hasColumn('cleaning_processes', 'output_item_id')) {
                $table->dropConstrainedForeignId('output_item_id');
            }
        });
    }

    private function rawCleanItemId(
        string $inputSku,
        string $inputName,
        int $categoryId,
        int $unitId,
        int $stageId,
        float $purchasePrice,
        float $sellingPrice,
        mixed $now,
    ): int {
        $sku = str_starts_with($inputSku, 'RM-')
            ? 'RC-'.substr($inputSku, 3)
            : 'RC-'.$inputSku;

        DB::table('items')->updateOrInsert(
            ['sku' => $sku],
            [
                'name' => $inputName,
                'item_category_id' => $categoryId,
                'default_unit_id' => $unitId,
                'default_stage_id' => $stageId,
                'item_type' => 'material',
                'requires_production' => false,
                'is_perishable' => false,
                'minimum_stock' => 0,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'latest_weighted_avg_cost' => 0,
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return (int) DB::table('items')->where('sku', $sku)->value('id');
    }
};
