<?php

namespace App\Filament\Resources\BranchRequests\Pages;

use App\Enums\BranchRequestStatus;
use App\Enums\BranchRequestItemStatus;
use App\Enums\ItemStageCode;
use App\Filament\Resources\BranchRequests\BranchRequestResource;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\BranchRequestService;
use Filament\Actions\Action;
use App\Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateBranchRequest extends CreateRecord
{
    protected static string $resource = BranchRequestResource::class;

    protected static bool $canCreateAnother = false;

    public static string|Alignment $formActionsAlignment = Alignment::End;

    public static bool $formActionsAreSticky = true;

    public function getTitle(): string
    {
        return 'Buat Request Barang';
    }

    public function getSubheading(): ?string
    {
        return 'Ajukan kebutuhan barang jadi cabang untuk pengiriman berikutnya.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_requests')
                ->label('Kembali ke Request Cabang')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(BranchRequestResource::getUrl('index')),
        ];
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Kirim Request ke Gudang')
            ->icon('heroicon-m-paper-airplane')
            ->disabled(fn (): bool => empty(array_filter($this->data['items'] ?? [], fn (array $item): bool => filled($item['product_id'] ?? null))));
    }

    public function addProductToRequest(int $itemId): void
    {
        $item = Item::query()
            ->with(['category:id,name', 'defaultStage:id,code', 'defaultUnit:id,code,name'])
            ->where('is_active', true)
            ->whereHas('defaultStage', fn (Builder $query) => $query->where('code', ItemStageCode::FinishedGoods->value))
            ->find($itemId);

        if (! $item) {
            Notification::make()
                ->title('Produk tidak dapat ditambahkan')
                ->body('Hanya produk Finished Goods yang dapat diajukan oleh cabang.')
                ->danger()
                ->send();

            return;
        }

        if (! $item->default_unit_id) {
            Notification::make()
                ->title('Produk belum memiliki unit default')
                ->warning()
                ->send();

            return;
        }

        $data = $this->data ?? [];
        $items = $data['items'] ?? [];

        foreach ($items as $key => $line) {
            if ((int) ($line['product_id'] ?? 0) !== $item->id) {
                continue;
            }

            $items[$key]['requested_qty'] = max((float) ($line['requested_qty'] ?? 0), 0) + 1;
            $items[$key]['unit_id'] = $item->default_unit_id;
            $items[$key]['category'] = $item->category?->name;
            $items[$key]['stock_available'] = $this->availableStock($item->id);

            $data['items'] = $items;
            $this->data = $data;

            Notification::make()
                ->title('Produk sudah ada di request')
                ->body('Qty request ditambah 1.')
                ->success()
                ->send();

            return;
        }

        $items[(string) Str::uuid()] = [
            'product_id' => $item->id,
            'category' => $item->category?->name,
            'requested_qty' => 1,
            'approved_qty' => 0,
            'packed_qty' => 0,
            'shipped_qty' => 0,
            'received_qty' => 0,
            'unit_id' => $item->default_unit_id,
            'stock_available' => $this->availableStock($item->id),
            'branch_note' => null,
            'warehouse_note' => null,
            'item_status' => BranchRequestItemStatus::Requested->value,
        ];

        $data['items'] = $items;
        $this->data = $data;

        Notification::make()
            ->title('Produk ditambahkan ke request')
            ->success()
            ->send();
    }

    protected function availableStock(int $itemId): float
    {
        $user = Auth::user();
        $branchId = $user instanceof User && $user->isBranchLike()
            ? $user->branch_id
            : ($this->data['branch_id'] ?? null);

        if (! $branchId) {
            return 0.0;
        }

        return (float) StockBalance::query()
            ->where('item_id', $itemId)
            ->where('branch_id', $branchId)
            ->sum('qty_on_hand');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $data['created_by'] = $user->id;
            if ($user->isBranchLike()) {
                $data['branch_id'] = $user->branch_id;
            }
        }

        $data['request_number'] = $data['request_number'] ?? 'REQ-'.now()->format('YmdHis');
        $data['request_date'] = $data['request_date'] ?? now()->toDateString();
        $data['status'] = $data['status'] ?? BranchRequestStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            app(BranchRequestService::class)->prepareDraft($this->record, $user);
        }
    }
}
