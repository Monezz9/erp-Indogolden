<?php

namespace Database\Seeders;

use App\Models\ProductionRecipe;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ProductionService;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'adminlogistik@erp.local')->first();
        $head = User::query()->where('email', 'headlogistik@erp.local')->first();

        if (! $admin) {
            return;
        }

        $recipe = ProductionRecipe::query()->where('code', 'RC-PST-KENCUR')->first();

        if (! $recipe) {
            return;
        }

        $productionWarehouse = Warehouse::query()->where('code', 'WH-PROD')->first();

        /** @var ProductionService $productionService */
        $productionService = app(ProductionService::class);

        $order = $productionService->createOrder($recipe, 50, $admin);

        foreach ($order->inputs as $input) {
            $input->update([
                'unit_cost' => $input->item->latest_weighted_avg_cost,
                'total_cost' => (float) $input->actual_qty * (float) $input->item->latest_weighted_avg_cost,
            ]);
        }

        foreach ($order->outputs as $output) {
            $output->update([
                'unit_cost' => 75000,
                'total_cost' => (float) $output->qty * 75000,
                'stage_id' => $output->item->default_stage_id,
                'warehouse_id' => $productionWarehouse?->id,
            ]);
        }

        $productionService->submitOrder($order, $admin);

        if ($head) {
            $productionService->approveOrder($order->fresh(), $head);
        }

        $productionService->completeOrder($order->fresh(['inputs.item', 'outputs.item']), $admin, $productionWarehouse?->id);
    }
}
