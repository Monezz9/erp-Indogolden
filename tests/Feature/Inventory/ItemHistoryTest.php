<?php

namespace Tests\Feature\Inventory;

use App\Enums\ApprovalStatus;
use App\Enums\MovementType;
use App\Filament\Resources\Items\Pages\ItemHistory;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ItemHistoryTest extends TestCase
{
    public function test_item_history_shows_running_balance_and_summary_from_stock_movements(): void
    {
        $this->prepareDatabase();

        $user = $this->createUserWithRole('inventory-history@erp.test', 'owner');
        $this->actingAs($user);

        [$item, $unit, $stage] = $this->createItemFixture();

        StockBalance::query()->create([
            'balance_key' => implode(':', [$item->id, $stage->id, 0, 0, 0]),
            'item_id' => $item->id,
            'stage_id' => $stage->id,
            'qty_on_hand' => 7,
            'avg_cost' => 120,
            'total_value' => 840,
        ]);

        $inbound = StockMovement::query()->create([
            'movement_number' => 'SM-HIST-IN',
            'movement_date' => now()->subDay(),
            'movement_type' => MovementType::InboundPurchase->value,
            'status' => ApprovalStatus::Approved,
            'total_cost' => 1200,
            'notes' => 'Pengadaan test',
            'created_by' => $user->id,
        ]);
        $inbound->items()->create([
            'item_id' => $item->id,
            'unit_id' => $unit->id,
            'direction' => 'in',
            'qty' => 10,
            'unit_cost' => 120,
            'total_cost' => 1200,
            'to_stage_id' => $stage->id,
            'notes' => 'Masuk test',
        ]);

        $this->createMovementLine($item, $unit, $stage, $user, 'SM-HIST-GROOM', MovementType::CleaningConversion, 'out', 1, 'Grooming test');
        $outbound = $this->createMovementLine($item, $unit, $stage, $user, 'SM-HIST-OUT', MovementType::ProductionConsumption, 'out', 3, 'Produksi test');
        $this->createMovementLine($item, $unit, $stage, $user, 'SM-HIST-RETUR', MovementType::WasteShrinkage, 'out', 1, 'Retur test');
        $this->createMovementLine($item, $unit, $stage, $user, 'SM-HIST-REQ', MovementType::BranchSale, 'out', 1, 'Request cabang test');
        $this->createMovementLine($item, $unit, $stage, $user, 'SM-HIST-SHIP', MovementType::BranchTransfer, 'out', 1, 'Pengiriman cabang test');

        $outbound->items()->firstOrFail()->update([
            'item_id' => $item->id,
            'unit_id' => $unit->id,
            'direction' => 'out',
            'qty' => 3,
            'unit_cost' => 120,
            'total_cost' => 360,
            'from_stage_id' => $stage->id,
        ]);

        $page = app(ItemHistory::class);
        $page->mount($item->id);
        $rows = $page->historyRows();
        $summary = $page->summary();

        $this->assertCount(6, $rows);
        $this->assertSame('Pengadaan', $rows[0]['movement_label']);
        $this->assertSame(10.0, $rows[0]['in_qty']);
        $this->assertSame(10.0, $rows[0]['balance_after']);
        $this->assertContains('Grooming', $rows->pluck('movement_label')->all());
        $this->assertContains('Produksi', $rows->pluck('movement_label')->all());
        $this->assertContains('Retur', $rows->pluck('movement_label')->all());
        $this->assertContains('Request Cabang', $rows->pluck('movement_label')->all());
        $this->assertContains('Pengiriman Cabang', $rows->pluck('movement_label')->all());
        $this->assertSame(7.0, $summary['current_stock']);
        $this->assertSame(10.0, $summary['total_in']);
        $this->assertSame(7.0, $summary['total_out']);

        $page->movementType = 'Produksi';
        $this->assertCount(1, $page->historyRows());
        $page->movementType = 'all';
        $page->search = 'Pengiriman cabang';
        $this->assertCount(1, $page->historyRows());

        $page->search = null;
        $page->showHistoryDetail($rows[0]['id']);
        $this->assertSame($rows[0]['id'], $page->selectedHistoryRow()['id']);
    }

    protected function createMovementLine(
        Item $item,
        Unit $unit,
        ItemStage $stage,
        User $user,
        string $number,
        MovementType $type,
        string $direction,
        float $qty,
        string $notes,
    ): StockMovement {
        $movement = StockMovement::query()->create([
            'movement_number' => $number,
            'movement_date' => now(),
            'movement_type' => $type->value,
            'status' => ApprovalStatus::Approved,
            'total_cost' => $qty * 120,
            'notes' => $notes,
            'created_by' => $user->id,
        ]);

        $movement->items()->create([
            'item_id' => $item->id,
            'unit_id' => $unit->id,
            'direction' => $direction,
            'qty' => $qty,
            'unit_cost' => 120,
            'total_cost' => $qty * 120,
            'from_stage_id' => $direction === 'out' ? $stage->id : null,
            'to_stage_id' => $direction === 'in' ? $stage->id : null,
            'notes' => $notes,
        ]);

        return $movement;
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

    /**
     * @return array{0: Item, 1: Unit, 2: ItemStage}
     */
    protected function createItemFixture(): array
    {
        $unit = Unit::query()->updateOrCreate(['code' => 'GR'], [
            'name' => 'Gram',
            'is_base' => true,
            'precision' => 4,
            'is_active' => true,
        ]);
        $category = ItemCategory::query()->create([
            'name' => 'Raw Material',
            'slug' => 'raw-material-history',
            'category_type' => 'raw_material',
            'is_active' => true,
        ]);
        $stage = ItemStage::query()->updateOrCreate(['code' => 'raw_dirty'], [
            'name' => 'Raw Dirty',
            'sequence' => 1,
            'is_active' => true,
        ]);
        $item = Item::query()->create([
            'sku' => 'RM-HISTORY',
            'name' => 'RM History',
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $stage->id,
            'item_type' => 'material',
            'requires_production' => false,
            'is_perishable' => false,
            'minimum_stock' => 0,
            'latest_weighted_avg_cost' => 120,
            'is_active' => true,
        ]);

        return [$item, $unit, $stage];
    }

    protected function prepareDatabase(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ekstensi pdo_sqlite belum tersedia pada environment ini.');
        }

        $this->artisan('migrate:fresh');
    }
}
