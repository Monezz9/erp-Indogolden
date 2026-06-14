<?php

namespace Database\Seeders;

use App\Enums\MovementType;
use App\Models\Item;
use App\Models\ItemStage;
use App\Models\Transfer;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockMovementService;
use App\Services\TransferService;
use Illuminate\Database\Seeder;

class InventoryTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'adminlogistik@erp.local')->firstOrFail();
        $head = User::query()->where('email', 'headlogistik@erp.local')->firstOrFail();

        $whCentral = Warehouse::query()->where('code', 'WH-CENTRAL')->firstOrFail();
        $whProd = Warehouse::query()->where('code', 'WH-PROD')->firstOrFail();
        $whCandiPanggung = Warehouse::query()->where('code', 'WH-CPG')->firstOrFail();

        $stageRawDirty = ItemStage::query()->where('code', 'raw_dirty')->value('id');
        $stageSrm = ItemStage::query()->where('code', 'srm')->value('id');
        $stageFinished = ItemStage::query()->where('code', 'finished_goods')->value('id');

        $itemKencur = Item::query()->where('sku', 'RM-KENCUR')->firstOrFail();
        $itemSrmKencur = Item::query()->where('sku', 'SRM-KENCUR')->firstOrFail();
        $itemRawit = Item::query()->where('sku', 'RM-RAWIT')->firstOrFail();
        $itemMinyak = Item::query()->where('sku', 'RM-MINYAK')->firstOrFail();
        $itemCup = Item::query()->where('sku', 'MRO-CUP')->firstOrFail();
        $itemCuanki = Item::query()->where('sku', 'FG-CUANKI')->firstOrFail();

        $unitKg = Unit::query()->where('code', 'KG')->firstOrFail();
        $unitLtr = Unit::query()->where('code', 'LTR')->firstOrFail();
        $unitPcs = Unit::query()->where('code', 'PCS')->firstOrFail();
        $unitPack = Unit::query()->where('code', 'PACK')->firstOrFail();

        /** @var StockMovementService $stockMovementService */
        $stockMovementService = app(StockMovementService::class);

        $movements = [
            [
                'movement_type' => MovementType::InboundPurchase->value,
                'notes' => 'Inbound bahan mentah dari supplier',
                'items' => [
                    [
                        'item_id' => $itemKencur->id,
                        'unit_id' => $unitKg->id,
                        'direction' => 'in',
                        'qty' => 50,
                        'unit_cost' => 50000,
                        'to_stage_id' => $stageRawDirty,
                        'to_warehouse_id' => $whCentral->id,
                    ],
                    [
                        'item_id' => $itemRawit->id,
                        'unit_id' => $unitKg->id,
                        'direction' => 'in',
                        'qty' => 20,
                        'unit_cost' => 65000,
                        'to_stage_id' => $stageRawDirty,
                        'to_warehouse_id' => $whCentral->id,
                    ],
                    [
                        'item_id' => $itemMinyak->id,
                        'unit_id' => $unitLtr->id,
                        'direction' => 'in',
                        'qty' => 30,
                        'unit_cost' => 19500,
                        'to_stage_id' => $stageSrm,
                        'to_warehouse_id' => $whProd->id,
                    ],
                ],
            ],
            [
                'movement_type' => MovementType::CleaningConversion->value,
                'notes' => 'Konversi bahan mentah kotor ke mentah bersih',
                'items' => [
                    [
                        'item_id' => $itemKencur->id,
                        'unit_id' => $unitKg->id,
                        'direction' => 'out',
                        'qty' => 20,
                        'unit_cost' => 50000,
                        'from_stage_id' => $stageRawDirty,
                        'from_warehouse_id' => $whCentral->id,
                    ],
                    [
                        'item_id' => $itemSrmKencur->id,
                        'unit_id' => $unitKg->id,
                        'direction' => 'in',
                        'qty' => 19,
                        'unit_cost' => 50000,
                        'to_stage_id' => $stageSrm,
                        'to_warehouse_id' => $whProd->id,
                    ],
                ],
            ],
            [
                'movement_type' => MovementType::WasteShrinkage->value,
                'notes' => 'Susut proses pembersihan',
                'items' => [
                    [
                        'item_id' => $itemKencur->id,
                        'unit_id' => $unitKg->id,
                        'direction' => 'out',
                        'qty' => 1,
                        'unit_cost' => 50000,
                        'from_stage_id' => $stageRawDirty,
                        'from_warehouse_id' => $whCentral->id,
                    ],
                ],
            ],
            [
                'movement_type' => MovementType::InboundPurchase->value,
                'notes' => 'Inbound barang jadi dan mro',
                'items' => [
                    [
                        'item_id' => $itemCuanki->id,
                        'unit_id' => $unitPack->id,
                        'direction' => 'in',
                        'qty' => 150,
                        'unit_cost' => 245000,
                        'to_stage_id' => $stageFinished,
                        'to_warehouse_id' => $whCentral->id,
                    ],
                    [
                        'item_id' => $itemCup->id,
                        'unit_id' => $unitPcs->id,
                        'direction' => 'in',
                        'qty' => 2000,
                        'unit_cost' => 540,
                        'to_stage_id' => $itemCup->default_stage_id,
                        'to_warehouse_id' => $whCentral->id,
                    ],
                ],
            ],
        ];

        foreach ($movements as $movementData) {
            $movement = $stockMovementService->createDraft(
                movementData: [
                    'movement_number' => 'SM-SEED-'.strtoupper(substr(uniqid(), -8)),
                    'movement_date' => now(),
                    'movement_type' => $movementData['movement_type'],
                    'notes' => $movementData['notes'],
                    'created_by' => $admin->id,
                ],
                items: $movementData['items'],
            );

            $stockMovementService->submit($movement);
            $stockMovementService->approve($movement, $head);
        }

        $transfer = Transfer::query()->create([
            'transfer_number' => 'TRF-SEED-001',
            'transfer_date' => now(),
            'status' => 'draft',
            'from_warehouse_id' => $whCentral->id,
            'to_warehouse_id' => $whCandiPanggung->id,
            'from_branch_id' => $whCentral->branch_id,
            'to_branch_id' => $whCandiPanggung->branch_id,
            'requested_by' => $admin->id,
            'notes' => 'Transfer barang jadi ke Cabang Candi Panggung',
        ]);

        $transfer->items()->create([
            'item_id' => $itemCuanki->id,
            'unit_id' => $unitPack->id,
            'requested_qty' => 30,
            'approved_qty' => 30,
            'shipped_qty' => 30,
            'received_qty' => 30,
            'unit_cost' => 245000,
            'total_cost' => 7350000,
        ]);

        /** @var TransferService $transferService */
        $transferService = app(TransferService::class);
        $transferService->submit($transfer);
        $transferService->approve($transfer, $head);
        $transferService->ship($transfer, $admin);
        $transferService->receive($transfer, $head);
    }
}
