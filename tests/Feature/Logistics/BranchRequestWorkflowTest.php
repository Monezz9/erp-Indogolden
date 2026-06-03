<?php

namespace Tests\Feature\Logistics;

use App\Enums\BranchRequestItemStatus;
use App\Enums\BranchRequestStatus;
use App\Models\Branch;
use App\Models\BranchRequest;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\Unit;
use App\Models\User;
use App\Services\BranchRequestService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchRequestWorkflowTest extends TestCase
{
    public function test_branch_request_happy_path_transitions_work(): void
    {
        $this->prepareDatabase();

        $branch = Branch::query()->create([
            'code' => 'CBG-JKT',
            'name' => 'Cabang Jakarta',
            'is_active' => true,
        ]);

        $unit = Unit::query()->firstOrCreate(['code' => 'PCS'], [
            'name' => 'Pieces',
            'is_base' => true,
            'precision' => 0,
            'is_active' => true,
        ]);

        $category = ItemCategory::query()->create([
            'name' => 'Bahan Baku',
            'slug' => 'bahan-baku',
            'category_type' => 'raw_material',
            'is_active' => true,
        ]);

        $stage = ItemStage::query()->firstOrCreate(['code' => 'raw_dirty'], [
            'name' => 'Raw Dirty',
            'sequence' => 1,
            'is_active' => true,
        ]);

        $item = Item::query()->create([
            'sku' => 'SKU-BRG-001',
            'name' => 'Produk Uji',
            'item_category_id' => $category->id,
            'default_unit_id' => $unit->id,
            'default_stage_id' => $stage->id,
            'item_type' => 'material',
            'requires_production' => false,
            'is_perishable' => false,
            'minimum_stock' => 0,
            'latest_weighted_avg_cost' => 0,
            'is_active' => true,
        ]);

        $cabang = $this->createUserWithRole('cabang@erp.test', 'cabang', $branch->id);
        $gudang = $this->createUserWithRole('gudang@erp.test', 'gudang', null);

        $request = BranchRequest::query()->create([
            'request_number' => 'REQ-TEST-0001',
            'branch_id' => $branch->id,
            'request_date' => now()->toDateString(),
            'delivery_date' => now()->addDay()->toDateString(),
            'status' => BranchRequestStatus::Draft->value,
            'created_by' => $cabang->id,
        ]);

        $request->items()->create([
            'product_id' => $item->id,
            'requested_qty' => 10,
            'approved_qty' => 0,
            'packed_qty' => 0,
            'shipped_qty' => 0,
            'received_qty' => 0,
            'unit_id' => $unit->id,
            'stock_available' => 25,
            'item_status' => BranchRequestItemStatus::Requested->value,
        ]);

        $service = app(BranchRequestService::class);

        $request = $service->submit($request, $cabang);
        $this->assertSame(BranchRequestStatus::Submitted->value, $request->status);

        $request = $service->review($request, $gudang);
        $this->assertSame(BranchRequestStatus::Reviewed->value, $request->status);

        $request = $service->approve($request, $gudang);
        $this->assertSame(BranchRequestStatus::Approved->value, $request->status);
        $this->assertSame(10.0, (float) $request->items()->first()->approved_qty);

        $request = $service->markPacked($request, $gudang);
        $this->assertSame(BranchRequestStatus::Packed->value, $request->status);
        $this->assertSame(BranchRequestItemStatus::Packed->value, $request->items()->first()->item_status);

        $request = $service->markShipped($request, $gudang);
        $this->assertSame(BranchRequestStatus::Shipped->value, $request->status);
        $this->assertSame(BranchRequestItemStatus::Shipped->value, $request->items()->first()->item_status);

        $request = $service->markReceived($request, $cabang);
        $this->assertSame(BranchRequestStatus::Received->value, $request->status);
        $this->assertSame(BranchRequestItemStatus::Received->value, $request->items()->first()->item_status);
    }

    public function test_invalid_transition_throws_runtime_exception(): void
    {
        $this->prepareDatabase();

        $branch = Branch::query()->create([
            'code' => 'CBG-BDG',
            'name' => 'Cabang Bandung',
            'is_active' => true,
        ]);

        $gudang = $this->createUserWithRole('gudang2@erp.test', 'gudang', null);

        $request = BranchRequest::query()->create([
            'request_number' => 'REQ-TEST-0002',
            'branch_id' => $branch->id,
            'request_date' => now()->toDateString(),
            'delivery_date' => now()->addDay()->toDateString(),
            'status' => BranchRequestStatus::Draft->value,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tidak valid');

        app(BranchRequestService::class)->markShipped($request, $gudang);
    }

    protected function createUserWithRole(string $email, string $role, ?int $branchId): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create([
            'email' => $email,
            'branch_id' => $branchId,
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
