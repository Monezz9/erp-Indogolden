<?php

use App\Enums\ItemStageCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $wipCategoryId = DB::table('item_categories')->where('slug', 'wip')->value('id');
        $fgCategoryId = DB::table('item_categories')->where('slug', 'finished-goods')->value('id');
        $ballUnitId = DB::table('units')->where('code', 'BALL')->value('id');
        $pcsUnitId = DB::table('units')->where('code', 'PCS')->value('id');
        $wipStageId = DB::table('item_stages')->where('code', ItemStageCode::Wip->value)->value('id');
        $fgStageId = DB::table('item_stages')->where('code', ItemStageCode::FinishedGoods->value)->value('id');
        $warehouse = DB::table('warehouses')
            ->where('code', 'WH-CENTRAL')
            ->orWhere('location_type', 'central')
            ->orWhere('name', 'Gudang Pusat')
            ->orderByRaw("CASE WHEN code = 'WH-CENTRAL' THEN 0 ELSE 1 END")
            ->first(['id', 'branch_id']);

        if (! $wipCategoryId || ! $fgCategoryId || ! $ballUnitId || ! $pcsUnitId || ! $wipStageId || ! $fgStageId || ! $warehouse) {
            return;
        }

        foreach ($this->items() as $item) {
            $srmId = $this->upsertItem(
                sku: $item['srm_sku'],
                name: $item['name'],
                categoryId: (int) $wipCategoryId,
                unitId: (int) $ballUnitId,
                stageId: (int) $wipStageId,
                type: 'semi_finished',
                price: $item['cost'],
                now: $now,
            );

            $this->upsertItem(
                sku: $item['fg_sku'],
                name: $item['name'],
                categoryId: (int) $fgCategoryId,
                unitId: (int) $pcsUnitId,
                stageId: (int) $fgStageId,
                type: 'product',
                price: 0,
                now: $now,
            );

            $this->upsertBalance(
                itemId: $srmId,
                stageId: (int) $wipStageId,
                warehouseId: (int) $warehouse->id,
                branchId: $warehouse->branch_id ? (int) $warehouse->branch_id : null,
                qty: $item['stock'],
                avgCost: $item['cost'],
                now: $now,
            );
        }
    }

    public function down(): void
    {
        // Keep operational master data intact on rollback.
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(): array
    {
        return [
            ['srm_sku' => 'SRM-CUANKI', 'fg_sku' => 'FG-CUANKI', 'name' => 'Cuanki', 'stock' => 3, 'cost' => 245000],
            ['srm_sku' => 'SRM-SIOMAY-MINI', 'fg_sku' => 'FG-SIOMAY-MINI', 'name' => 'Siomay Mini', 'stock' => 2, 'cost' => 140000],
            ['srm_sku' => 'SRM-PILUS', 'fg_sku' => 'FG-PILUS', 'name' => 'Pilus', 'stock' => 2, 'cost' => 140000],
            ['srm_sku' => 'SRM-POKCOY', 'fg_sku' => 'FG-POKCOY', 'name' => 'Pokcoy', 'stock' => 15, 'cost' => 10000],
            ['srm_sku' => 'SRM-JAMUR-ENOKI', 'fg_sku' => 'FG-JAMUR-ENOKI', 'name' => 'Jamur Enoki', 'stock' => 1, 'cost' => 140000],
            ['srm_sku' => 'SRM-KWETIAU', 'fg_sku' => 'FG-KWETIAU', 'name' => 'Kwetiau', 'stock' => 10, 'cost' => 8000],
        ];
    }

    private function upsertItem(
        string $sku,
        string $name,
        int $categoryId,
        int $unitId,
        int $stageId,
        string $type,
        float $price,
        mixed $now,
    ): int {
        DB::table('items')->updateOrInsert(
            ['sku' => $sku],
            [
                'name' => $name,
                'item_category_id' => $categoryId,
                'default_unit_id' => $unitId,
                'default_stage_id' => $stageId,
                'item_type' => $type,
                'requires_production' => in_array($type, ['semi_finished', 'product'], true),
                'minimum_stock' => 0,
                'latest_weighted_avg_cost' => $price,
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        return (int) DB::table('items')->where('sku', $sku)->value('id');
    }

    private function upsertBalance(
        int $itemId,
        int $stageId,
        int $warehouseId,
        ?int $branchId,
        float $qty,
        float $avgCost,
        mixed $now,
    ): void {
        DB::table('stock_balances')->updateOrInsert(
            ['balance_key' => implode(':', [$itemId, $stageId, $warehouseId, $branchId ?? 0, 0])],
            [
                'item_id' => $itemId,
                'stage_id' => $stageId,
                'warehouse_id' => $warehouseId,
                'branch_id' => $branchId,
                'stock_batch_id' => null,
                'qty_on_hand' => $qty,
                'avg_cost' => $avgCost,
                'total_value' => $qty * $avgCost,
                'last_updated_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );
    }
};
