<?php

namespace Database\Seeders;

use App\Enums\BranchSaleStatus;
use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\BranchSale;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use App\Services\BranchSaleService;
use Illuminate\Database\Seeder;

class BranchSaleSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'BR-CPG')->first();
        $branchUser = User::query()->where('email', 'cabang.candipanggung@erp.local')->first();

        if (! $branch || ! $branchUser) {
            return;
        }

        $item = Item::query()->where('sku', 'FG-CUANKI')->first();

        if (! $item) {
            return;
        }

        $unit = Unit::query()->find($item->default_unit_id);

        if (! $unit) {
            return;
        }

        $sale = BranchSale::query()->updateOrCreate(
            ['sale_number' => 'NOTA-CPG-001'],
            [
                'sale_date' => now(),
                'branch_id' => $branch->id,
                'status' => BranchSaleStatus::Draft,
                'payment_method' => PaymentMethod::Cash,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'notes' => 'Contoh nota cabang dari seeder',
                'created_by' => $branchUser->id,
            ],
        );

        $sale->items()->updateOrCreate(
            ['item_id' => $item->id],
            [
                'unit_id' => $unit->id,
                'qty' => 9,
                'unit_price' => 290000,
                'line_total' => 2610000,
            ],
        );

        /** @var BranchSaleService $service */
        $service = app(BranchSaleService::class);
        $service->syncTotals($sale->fresh('items'));
        $service->post($sale->fresh('items.item.defaultStage'), $branchUser);
    }
}
