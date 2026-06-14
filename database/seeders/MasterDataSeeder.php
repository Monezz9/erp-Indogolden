<?php

namespace Database\Seeders;

use App\Enums\ItemStageCode;
use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\ProductionRecipe;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stages = [
            [
                'code' => ItemStageCode::RawDirty->value,
                'name' => 'Raw Dirty',
                'sequence' => 1,
            ],
            [
                'code' => ItemStageCode::RawClean->value,
                'name' => 'Raw Clean',
                'sequence' => 2,
            ],
            [
                'code' => ItemStageCode::Srm->value,
                'name' => 'SRM',
                'sequence' => 3,
            ],
            [
                'code' => ItemStageCode::Wip->value,
                'name' => 'Work in Process',
                'sequence' => 4,
            ],
            [
                'code' => ItemStageCode::FinishedGoods->value,
                'name' => 'Finished Goods',
                'sequence' => 5,
            ],
            [
                'code' => ItemStageCode::BranchStock->value,
                'name' => 'Branch Stock',
                'sequence' => 6,
            ],
            [
                'code' => ItemStageCode::Mro->value,
                'name' => 'MRO',
                'sequence' => 7,
            ],
            [
                'code' => ItemStageCode::Analysis->value,
                'name' => 'Analysis',
                'sequence' => 8,
            ],
        ];

        foreach ($stages as $stage) {
            ItemStage::query()->updateOrCreate(['code' => $stage['code']], $stage + ['is_active' => true]);
        }

        foreach ([
            ['code' => 'KG', 'name' => 'Kilogram', 'precision' => 3, 'is_base' => true],
            ['code' => 'GR', 'name' => 'Gram', 'precision' => 2, 'is_base' => false],
            ['code' => 'PCS', 'name' => 'Pieces', 'precision' => 0, 'is_base' => true],
            ['code' => 'LTR', 'name' => 'Liter', 'precision' => 2, 'is_base' => true],
            ['code' => 'PACK', 'name' => 'Pack', 'precision' => 0, 'is_base' => true],
            ['code' => 'BALL', 'name' => 'Ball', 'precision' => 0, 'is_base' => true],
        ] as $unit) {
            Unit::query()->updateOrCreate(['code' => $unit['code']], $unit + ['is_active' => true]);
        }

        foreach ([
            ['slug' => 'raw-material', 'name' => 'Raw Material', 'category_type' => 'raw_material'],
            ['slug' => 'srm', 'name' => 'SRM', 'category_type' => 'wip'],
            ['slug' => 'finished-goods', 'name' => 'Finished Goods', 'category_type' => 'finished_goods'],
            ['slug' => 'mro', 'name' => 'MRO', 'category_type' => 'mro'],
            ['slug' => 'analysis', 'name' => 'Analysis Item', 'category_type' => 'analysis'],
        ] as $category) {
            ItemCategory::query()->updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]);
        }

        $branchCodeAliases = [
            'BR-CPG' => 'BR-JKT',
            'BR-TBY' => 'BR-BKS',
        ];

        foreach ([
            ['code' => 'BR-HQ', 'name' => 'Head Office', 'city' => 'Malang', 'address' => 'Satu gedung dengan Cabang Candi Panggung'],
            ['code' => 'BR-CPG', 'name' => 'Cabang Candi Panggung', 'city' => 'Malang', 'address' => 'Candi Panggung, Malang'],
            ['code' => 'BR-TBY', 'name' => 'Cabang Teluk Bayur', 'city' => 'Malang', 'address' => 'Teluk Bayur, Malang'],
        ] as $branch) {
            $record = Branch::query()->where('code', $branch['code'])->first()
                ?? Branch::query()->where('code', $branchCodeAliases[$branch['code']] ?? $branch['code'])->first();

            $payload = [
                ...$branch,
                'phone' => '08'.random_int(1000000000, 9999999999),
                'is_active' => true,
            ];

            $record?->update($payload) ?? Branch::query()->create($payload);
        }

        $hq = Branch::query()->where('code', 'BR-HQ')->firstOrFail();
        $candiPanggung = Branch::query()->where('code', 'BR-CPG')->firstOrFail();
        $telukBayur = Branch::query()->where('code', 'BR-TBY')->firstOrFail();

        $warehouseCodeAliases = [
            'WH-CPG' => 'WH-JKT',
            'WH-TBY' => 'WH-BKS',
        ];

        foreach ([
            ['code' => 'WH-CENTRAL', 'name' => 'Gudang Pusat', 'branch_id' => $hq->id, 'location_type' => 'central'],
            ['code' => 'WH-PROD', 'name' => 'Gudang Produksi', 'branch_id' => $hq->id, 'location_type' => 'production'],
            ['code' => 'WH-CPG', 'name' => 'Gudang Cabang Candi Panggung', 'branch_id' => $candiPanggung->id, 'location_type' => 'branch'],
            ['code' => 'WH-TBY', 'name' => 'Gudang Cabang Teluk Bayur', 'branch_id' => $telukBayur->id, 'location_type' => 'branch'],
        ] as $warehouse) {
            $record = Warehouse::query()->where('code', $warehouse['code'])->first()
                ?? Warehouse::query()->where('code', $warehouseCodeAliases[$warehouse['code']] ?? $warehouse['code'])->first();

            $payload = [
                ...$warehouse,
                'is_active' => true,
            ];

            $record?->update($payload) ?? Warehouse::query()->create($payload);
        }

        foreach ([
            ['code' => 'SUP-001', 'name' => 'PT Bumbu Nusantara'],
            ['code' => 'SUP-002', 'name' => 'CV Sayur Segar'],
            ['code' => 'SUP-003', 'name' => 'PT Kemasan Prima'],
        ] as $supplier) {
            Supplier::query()->updateOrCreate(['code' => $supplier['code']], [
                ...$supplier,
                'contact_person' => 'PIC '.$supplier['code'],
                'phone' => '08'.random_int(1000000000, 9999999999),
                'is_active' => true,
            ]);
        }

        $categories = ItemCategory::query()->pluck('id', 'slug');
        $units = Unit::query()->pluck('id', 'code');
        $stageIds = ItemStage::query()->pluck('id', 'code');

        $items = [
            ['sku' => 'RM-KENCUR', 'name' => 'Kencur', 'category' => 'raw-material', 'unit' => 'KG', 'stage' => ItemStageCode::RawDirty->value, 'type' => 'material'],
            ['sku' => 'RM-CKER', 'name' => 'Cabe Keriting', 'category' => 'raw-material', 'unit' => 'KG', 'stage' => ItemStageCode::RawDirty->value, 'type' => 'material'],
            ['sku' => 'RM-RAWIT', 'name' => 'Cabe Rawit', 'category' => 'raw-material', 'unit' => 'KG', 'stage' => ItemStageCode::RawDirty->value, 'type' => 'material'],
            ['sku' => 'SRM-KENCUR', 'name' => 'Kencur', 'category' => 'srm', 'unit' => 'KG', 'stage' => ItemStageCode::Srm->value, 'type' => 'semi_finished'],
            ['sku' => 'RM-BAPUT', 'name' => 'Bawang Putih', 'category' => 'srm', 'unit' => 'KG', 'stage' => ItemStageCode::Srm->value, 'type' => 'semi_finished'],
            ['sku' => 'RM-MINYAK', 'name' => 'Minyak', 'category' => 'srm', 'unit' => 'LTR', 'stage' => ItemStageCode::Srm->value, 'type' => 'semi_finished'],
            ['sku' => 'RM-PENYEDAP', 'name' => 'Penyedap', 'category' => 'srm', 'unit' => 'GR', 'stage' => ItemStageCode::Srm->value, 'type' => 'semi_finished'],
            ['sku' => 'RM-MICIN', 'name' => 'Micin', 'category' => 'srm', 'unit' => 'GR', 'stage' => ItemStageCode::Srm->value, 'type' => 'semi_finished'],
            ['sku' => 'RM-GARAM', 'name' => 'Garam', 'category' => 'srm', 'unit' => 'GR', 'stage' => ItemStageCode::Srm->value, 'type' => 'semi_finished'],
            ['sku' => 'RM-LADA', 'name' => 'Lada', 'category' => 'srm', 'unit' => 'GR', 'stage' => ItemStageCode::Srm->value, 'type' => 'semi_finished'],
            ['sku' => 'RM-GULA', 'name' => 'Gula', 'category' => 'srm', 'unit' => 'GR', 'stage' => ItemStageCode::Srm->value, 'type' => 'semi_finished'],
            ['sku' => 'WIP-PREMIX', 'name' => 'Premix Bumbu', 'category' => 'srm', 'unit' => 'KG', 'stage' => ItemStageCode::Srm->value, 'type' => 'semi_finished'],
            ['sku' => 'FG-PREMIX', 'name' => 'Premix', 'category' => 'finished-goods', 'unit' => 'PCS', 'stage' => ItemStageCode::FinishedGoods->value, 'type' => 'product'],
            ['sku' => 'FG-PASTA-K', 'name' => 'Pasta Kencur', 'category' => 'finished-goods', 'unit' => 'PCS', 'stage' => ItemStageCode::FinishedGoods->value, 'type' => 'product'],
            ['sku' => 'FG-CUANKI', 'name' => 'Cuanki Frozen', 'category' => 'finished-goods', 'unit' => 'PACK', 'stage' => ItemStageCode::FinishedGoods->value, 'type' => 'product'],
            ['sku' => 'MRO-CUP', 'name' => 'Cup Plastik', 'category' => 'mro', 'unit' => 'PCS', 'stage' => ItemStageCode::Mro->value, 'type' => 'packaging'],
            ['sku' => 'MRO-PLASTIK-PREMIX', 'name' => 'Plastik Premix', 'category' => 'mro', 'unit' => 'PCS', 'stage' => ItemStageCode::Mro->value, 'type' => 'packaging'],
            ['sku' => 'AN-GULACAIR', 'name' => 'Gula Cair', 'category' => 'analysis', 'unit' => 'LTR', 'stage' => ItemStageCode::Analysis->value, 'type' => 'semi_finished'],
        ];

        foreach ($items as $item) {
            Item::query()->updateOrCreate(['sku' => $item['sku']], [
                'name' => $item['name'],
                'item_category_id' => $categories[$item['category']],
                'default_unit_id' => $units[$item['unit']],
                'default_stage_id' => $stageIds[$item['stage']],
                'item_type' => $item['type'],
                'requires_production' => in_array($item['type'], ['semi_finished', 'product'], true),
                'minimum_stock' => 10,
                'latest_weighted_avg_cost' => random_int(5000, 95000),
                'description' => 'Auto seeded item '.Str::lower($item['name']),
                'is_active' => true,
            ]);
        }

        $kencurPaste = Item::query()->where('sku', 'FG-PASTA-K')->firstOrFail();

        $recipe = ProductionRecipe::query()->updateOrCreate(
            ['code' => 'RC-PST-KENCUR'],
            [
                'name' => 'Pasta Kencur Standard',
                'output_item_id' => $kencurPaste->id,
                'output_unit_id' => $units['PCS'],
                'output_qty' => 100,
                'yield_percentage' => 95,
                'is_active' => true,
            ],
        );

        $recipe->ingredients()->delete();
        $recipe->ingredients()->createMany([
            [
                'item_id' => Item::query()->where('sku', 'SRM-KENCUR')->value('id'),
                'unit_id' => $units['KG'],
                'stage_id' => $stageIds[ItemStageCode::Srm->value],
                'qty' => 12,
                'is_optional' => false,
            ],
            [
                'item_id' => Item::query()->where('sku', 'RM-MINYAK')->value('id'),
                'unit_id' => $units['LTR'],
                'stage_id' => $stageIds[ItemStageCode::Srm->value],
                'qty' => 4,
                'is_optional' => false,
            ],
        ]);

        $premix = Item::query()->where('sku', 'FG-PREMIX')->firstOrFail();

        $premixRecipe = ProductionRecipe::query()->updateOrCreate(
            ['code' => 'RC-FG-PREMIX'],
            [
                'name' => 'Premix Standard',
                'output_item_id' => $premix->id,
                'output_unit_id' => $units['PCS'],
                'output_qty' => 1,
                'yield_percentage' => 100,
                'notes' => 'Resep spreadsheet 2/6: bahan gram menjadi 1 PCS Premix.',
                'is_active' => true,
            ],
        );

        $premixRecipe->ingredients()->delete();
        $premixRecipe->ingredients()->createMany([
            [
                'item_id' => Item::query()->where('sku', 'RM-PENYEDAP')->value('id'),
                'unit_id' => $units['GR'],
                'stage_id' => $stageIds[ItemStageCode::Srm->value],
                'qty' => 80,
                'is_optional' => false,
            ],
            [
                'item_id' => Item::query()->where('sku', 'RM-MICIN')->value('id'),
                'unit_id' => $units['GR'],
                'stage_id' => $stageIds[ItemStageCode::Srm->value],
                'qty' => 22,
                'is_optional' => false,
            ],
            [
                'item_id' => Item::query()->where('sku', 'RM-GARAM')->value('id'),
                'unit_id' => $units['GR'],
                'stage_id' => $stageIds[ItemStageCode::Srm->value],
                'qty' => 18,
                'is_optional' => false,
            ],
            [
                'item_id' => Item::query()->where('sku', 'RM-LADA')->value('id'),
                'unit_id' => $units['GR'],
                'stage_id' => $stageIds[ItemStageCode::Srm->value],
                'qty' => 10,
                'is_optional' => false,
            ],
            [
                'item_id' => Item::query()->where('sku', 'RM-GULA')->value('id'),
                'unit_id' => $units['GR'],
                'stage_id' => $stageIds[ItemStageCode::Srm->value],
                'qty' => 26,
                'is_optional' => false,
            ],
        ]);
    }
}
