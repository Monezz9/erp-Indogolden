<?php

namespace Tests\Feature\Production;

use App\Enums\ItemStageCode;
use App\Enums\MovementType;
use App\Filament\Pages\CleaningProcessWorkspace;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\StockBalance;
use App\Models\StockMovementItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CleaningProcessService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;

class CleaningProcessWorkflowTest extends TestCase
{
    public function test_rm_can_be_processed_to_srm_and_records_loss_audit(): void
    {
        $fixture = $this->cleaningFixture();

        $process = $this->startCleaning($fixture, [
            'input_qty' => 2000,
            'notes' => 'Grooming kencur masuk produksi',
        ]);

        $this->assertSame($fixture['rm']->id, $process->item_id);
        $this->assertSame($fixture['srm']->id, $process->output_item_id);
        $this->assertSame('in_progress', $process->status);
        $this->assertSame(0.0, (float) $process->output_qty);
        $this->assertSame(0.0, (float) $process->shrinkage_qty);
        $this->assertSame(60000.0, (float) $process->total_input_cost);
        $this->assertSame(0.0, (float) $process->output_unit_cost);

        $rmBalanceAfterStart = $this->balance($fixture['rm'], $fixture['rawDirty'], $fixture['warehouse']);
        $this->assertSame(1000.0, (float) $rmBalanceAfterStart->qty_on_hand);
        $this->assertSame(30000.0, (float) $rmBalanceAfterStart->total_value);
        $this->assertSame(0.0, $this->balanceQty($fixture['srm'], $fixture['srmStage'], $fixture['warehouse']));
        $this->assertDatabaseHas('stock_movement_items', [
            'item_id' => $fixture['rm']->id,
            'direction' => 'out',
            'qty' => 2000,
        ]);
        $this->assertDatabaseMissing('stock_movement_items', [
            'item_id' => $fixture['srm']->id,
            'direction' => 'in',
            'qty' => 1500,
        ]);

        $process = $this->completeCleaning($fixture, $process, [
            'output_qty' => 1500,
        ]);

        $this->assertSame('completed', $process->status);
        $this->assertSame(500.0, (float) $process->shrinkage_qty);
        $this->assertSame(25.0, (float) $process->shrinkage_percent);
        $this->assertSame(60000.0, (float) $process->total_input_cost);
        $this->assertEqualsWithDelta(40.0, (float) $process->output_unit_cost, 0.0001);

        $rmBalance = $this->balance($fixture['rm'], $fixture['rawDirty'], $fixture['warehouse']);
        $srmBalance = $this->balance($fixture['srm'], $fixture['srmStage'], $fixture['warehouse']);

        $this->assertSame(1000.0, (float) $rmBalance->qty_on_hand);
        $this->assertSame(30000.0, (float) $rmBalance->total_value);
        $this->assertSame(1500.0, (float) $srmBalance->qty_on_hand);
        $this->assertEqualsWithDelta(40.0, (float) $srmBalance->avg_cost, 0.0001);

        $this->assertDatabaseHas('stock_movement_items', [
            'item_id' => $fixture['srm']->id,
            'direction' => 'in',
            'qty' => 1500,
        ]);
        $this->assertDatabaseHas('stock_movement_items', [
            'item_id' => $fixture['rm']->id,
            'direction' => 'loss',
            'qty' => 500,
            'notes' => 'Susut grooming '.$process->process_number,
        ]);

        $lossLine = StockMovementItem::query()
            ->where('item_id', $fixture['rm']->id)
            ->where('direction', 'loss')
            ->with('movement')
            ->firstOrFail();

        $this->assertSame(MovementType::WasteShrinkage->value, $lossLine->movement->movement_type->value ?? $lossLine->movement->movement_type);
    }

    public function test_non_rm_is_rejected_by_grooming_service(): void
    {
        $fixture = $this->cleaningFixture();
        $fg = $this->createItem('FG-KENCUR', 'Kencur FG', 'finished-goods', 'Finished Goods', 'finished_goods', $fixture['rawDirty'], $fixture['unit']);

        StockBalance::query()->create([
            'balance_key' => "{$fg->id}:{$fixture['rawDirty']->id}:{$fixture['warehouse']->id}:0:0",
            'item_id' => $fg->id,
            'stage_id' => $fixture['rawDirty']->id,
            'warehouse_id' => $fixture['warehouse']->id,
            'qty_on_hand' => 1000,
            'avg_cost' => 30,
            'total_value' => 30000,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Hanya barang kategori RM yang dapat diproses grooming.');

        $this->startCleaning($fixture, [
            'item_id' => $fg->id,
            'input_qty' => 100,
            'notes' => 'Harus ditolak',
        ]);
    }

    public function test_output_item_must_be_srm(): void
    {
        $fixture = $this->cleaningFixture();
        $fg = $this->createItem('FG-OUTPUT', 'Output FG', 'finished-goods', 'Finished Goods', 'finished_goods', $fixture['srmStage'], $fixture['unit']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Output grooming harus barang kategori SRM.');

        $this->startCleaning($fixture, [
            'output_item_id' => $fg->id,
            'input_qty' => 100,
            'notes' => 'Output salah',
        ]);
    }

    public function test_auto_output_requires_existing_matching_srm_item(): void
    {
        $fixture = $this->cleaningFixture(includeSrmItem: false);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Item SRM tujuan belum tersedia. Buat master barang SRM terlebih dahulu.');

        $this->startCleaning($fixture, [
            'input_qty' => 100,
            'notes' => 'SRM belum ada',
        ]);
    }

    public function test_notes_are_required(): void
    {
        $fixture = $this->cleaningFixture();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Catatan grooming wajib diisi.');

        $this->startCleaning($fixture, [
            'input_qty' => 100,
            'notes' => '',
        ]);
    }

    public function test_minus_stock_is_blocked(): void
    {
        $fixture = $this->cleaningFixture();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Stok mentah kotor tidak cukup.');

        $this->startCleaning($fixture, [
            'input_qty' => 4000,
            'notes' => 'Stok kurang',
        ]);
    }

    public function test_grooming_item_options_only_show_rm_items(): void
    {
        $fixture = $this->cleaningFixture();
        $fg = $this->createItem('FG-RAW-STOCK', 'FG Raw Stock', 'finished-goods', 'Finished Goods', 'finished_goods', $fixture['rawDirty'], $fixture['unit']);

        StockBalance::query()->create([
            'balance_key' => "{$fg->id}:{$fixture['rawDirty']->id}:{$fixture['warehouse']->id}:0:0",
            'item_id' => $fg->id,
            'stage_id' => $fixture['rawDirty']->id,
            'warehouse_id' => $fixture['warehouse']->id,
            'qty_on_hand' => 1000,
            'avg_cost' => 30,
            'total_value' => 30000,
        ]);

        $page = app(CleaningProcessWorkspace::class);
        $page->warehouseId = $fixture['warehouse']->id;

        $options = $page->itemOptions();

        $this->assertArrayHasKey($fixture['rm']->id, $options);
        $this->assertArrayNotHasKey($fg->id, $options);
    }

    public function test_completed_grooming_cannot_be_completed_twice(): void
    {
        $fixture = $this->cleaningFixture();
        $process = $this->completeCleaning($fixture, $this->startCleaning($fixture), [
            'output_qty' => 1500,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Hanya grooming in progress yang dapat diselesaikan.');

        $this->completeCleaning($fixture, $process, [
            'output_qty' => 1400,
        ]);
    }

    protected function startCleaning(array $fixture, array $overrides = [])
    {
        return app(CleaningProcessService::class)->start([
            'warehouse_id' => $fixture['warehouse']->id,
            'item_id' => $fixture['rm']->id,
            'unit_id' => $fixture['unit']->id,
            'input_qty' => 2000,
            'notes' => 'Grooming bahan',
            ...$overrides,
        ], $fixture['user']);
    }

    protected function completeCleaning(array $fixture, $process, array $overrides = [])
    {
        return app(CleaningProcessService::class)->complete($process, [
            'output_qty' => 1500,
            ...$overrides,
        ], $fixture['user']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function cleaningFixture(bool $includeSrmItem = true): array
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('cleaning@erp.test', 'gudang');
        $unit = Unit::query()->firstOrCreate(['code' => 'GR'], [
            'name' => 'Gram',
            'is_base' => true,
            'precision' => 4,
            'is_active' => true,
        ]);
        $rawDirty = ItemStage::query()->firstOrCreate(['code' => ItemStageCode::RawDirty->value], [
            'name' => 'Raw Dirty',
            'sequence' => 1,
            'is_active' => true,
        ]);
        $srmStage = ItemStage::query()->firstOrCreate(['code' => ItemStageCode::Srm->value], [
            'name' => 'SRM',
            'sequence' => 2,
            'is_active' => true,
        ]);
        $warehouse = Warehouse::query()->create([
            'code' => 'WH-CLEAN',
            'name' => 'Warehouse Cleaning',
            'location_type' => 'central',
            'is_active' => true,
        ]);
        $rm = $this->createItem('RM-KENCUR', 'Kencur', 'raw-material', 'Raw Material', 'raw_material', $rawDirty, $unit);
        $srm = $includeSrmItem
            ? $this->createItem('SRM-KENCUR', 'Kencur', 'srm', 'SRM', 'wip', $srmStage, $unit, 'semi_finished')
            : null;

        StockBalance::query()->create([
            'balance_key' => "{$rm->id}:{$rawDirty->id}:{$warehouse->id}:0:0",
            'item_id' => $rm->id,
            'stage_id' => $rawDirty->id,
            'warehouse_id' => $warehouse->id,
            'qty_on_hand' => 3000,
            'avg_cost' => 30,
            'total_value' => 90000,
        ]);

        return compact('user', 'unit', 'rawDirty', 'srmStage', 'warehouse', 'rm', 'srm');
    }

    protected function createItem(
        string $sku,
        string $name,
        string $categorySlug,
        string $categoryName,
        string $categoryType,
        ItemStage $stage,
        Unit $unit,
        string $itemType = 'material',
    ): Item {
        $category = ItemCategory::query()->firstOrCreate(['slug' => $categorySlug], [
            'name' => $categoryName,
            'category_type' => $categoryType,
            'is_active' => true,
        ]);

        return Item::query()->create([
            'sku' => $sku,
            'name' => $name,
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $stage->id,
            'item_type' => $itemType,
            'requires_production' => false,
            'is_perishable' => false,
            'minimum_stock' => 0,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);
    }

    protected function balance(Item $item, ItemStage $stage, Warehouse $warehouse): StockBalance
    {
        return StockBalance::query()
            ->where('item_id', $item->id)
            ->where('stage_id', $stage->id)
            ->where('warehouse_id', $warehouse->id)
            ->firstOrFail();
    }

    protected function balanceQty(Item $item, ItemStage $stage, Warehouse $warehouse): float
    {
        return (float) StockBalance::query()
            ->where('item_id', $item->id)
            ->where('stage_id', $stage->id)
            ->where('warehouse_id', $warehouse->id)
            ->sum('qty_on_hand');
    }

    protected function createUserWithRole(string $email, string $role): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create([
            'email' => $email,
            'is_active' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }

    protected function prepareDatabase(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ekstensi pdo_sqlite belum tersedia pada environment ini.');
        }

        $this->artisan('migrate:fresh');
    }
}
