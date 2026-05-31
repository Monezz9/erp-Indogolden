<?php

namespace App\Filament\Pages;

use App\Enums\BranchRequestStatus;
use App\Enums\UserRole;
use App\Exports\StyledArrayExport;
use App\Models\BranchRequest;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TomorrowShipments extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected string $view = 'filament.pages.tomorrow-shipments';

    protected static ?string $navigationLabel = 'Pengiriman Besok';

    protected static \UnitEnum|string|null $navigationGroup = 'Operasional Cabang';

    protected static ?int $navigationSort = 3;

    public ?string $deliveryDate = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Admin->value,
            UserRole::Gudang->value,
            UserRole::Owner->value,
            UserRole::HeadLogistics->value,
            UserRole::LogisticsAdmin->value,
        ]);
    }

    public function mount(): void
    {
        $this->deliveryDate = now()->addDay()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Picking List')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn (): BinaryFileResponse => $this->exportConsolidated()),
        ];
    }

    public function groupedByBranch(): Collection
    {
        return $this->baseQuery()
            ->with(['branch', 'items.product', 'items.unit'])
            ->get()
            ->groupBy('branch.name');
    }

    public function consolidated(): Collection
    {
        return $this->baseQuery()
            ->with('items.product')
            ->get()
            ->flatMap->items
            ->groupBy('product_id')
            ->map(function (Collection $items): array {
                $first = $items->first();

                return [
                    'sku' => (string) ($first?->product?->sku ?? '-'),
                    'item' => (string) ($first?->product?->name ?? '-'),
                    'qty_request' => (float) $items->sum('requested_qty'),
                    'qty_approved' => (float) $items->sum('approved_qty'),
                    'qty_packed' => (float) $items->sum('packed_qty'),
                    'qty_shipped' => (float) $items->sum('shipped_qty'),
                ];
            })
            ->values();
    }

    protected function baseQuery()
    {
        return BranchRequest::query()
            ->whereDate('delivery_date', $this->deliveryDate)
            ->whereIn('status', [
                BranchRequestStatus::Submitted->value,
                BranchRequestStatus::Reviewed->value,
                BranchRequestStatus::Approved->value,
                BranchRequestStatus::Packed->value,
                BranchRequestStatus::Shipped->value,
            ]);
    }

    protected function exportConsolidated(): BinaryFileResponse
    {
        $rows = $this->consolidated();
        $columns = ['sku', 'item', 'qty_request', 'qty_approved', 'qty_packed', 'qty_shipped'];

        return app('excel')->download(
            new StyledArrayExport($rows, $columns, [
                'sku' => 'SKU',
                'item' => 'Barang',
                'qty_request' => 'Qty Diminta',
                'qty_approved' => 'Qty Disetujui',
                'qty_packed' => 'Qty Dipacking',
                'qty_shipped' => 'Qty Dikirim',
            ]),
            'picking_list_'.str($this->deliveryDate)->replace('-', '')->toString().'.xlsx',
        );
    }
}
