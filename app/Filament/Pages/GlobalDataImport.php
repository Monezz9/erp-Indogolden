<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Imports\HeadingRowsImport;
use App\Models\Branch;
use App\Models\BranchRequest;
use App\Models\BranchSale;
use App\Models\FinanceExpense;
use App\Models\FinanceIncome;
use App\Models\HppCalculation;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\ProductionOrder;
use App\Models\ShipmentBatch;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Excel\ResourceExcelManager;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class GlobalDataImport extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-on-square-stack';

    protected static ?string $navigationLabel = 'Import Data';

    protected static \UnitEnum|string|null $navigationGroup = 'Analisis Operasional';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.global-data-import';

    public string $module = 'items';

    public string $mode = 'upsert';

    public ?UploadedFile $file = null;

    /** @var array<int, array<string, mixed>> */
    public array $previewRows = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Admin->value,
            UserRole::Gudang->value,
            UserRole::Owner->value,
        ]);
    }

    /**
     * @return array<string, class-string<Model>>
     */
    public function moduleMap(): array
    {
        return [
            'branches' => Branch::class,
            'warehouses' => Warehouse::class,
            'suppliers' => Supplier::class,
            'units' => Unit::class,
            'item_categories' => ItemCategory::class,
            'item_stages' => ItemStage::class,
            'items' => Item::class,
            'stock_balances' => StockBalance::class,
            'stock_movements' => StockMovement::class,
            'transfers' => Transfer::class,
            'production_orders' => ProductionOrder::class,
            'finance_incomes' => FinanceIncome::class,
            'finance_expenses' => FinanceExpense::class,
            'users' => User::class,
            'branch_sales' => BranchSale::class,
            'branch_requests' => BranchRequest::class,
            'shipment_batches' => ShipmentBatch::class,
            'hpp_calculations' => HppCalculation::class,
        ];
    }

    public function preview(): void
    {
        if (! $this->file) {
            Notification::make()->title('Pilih file dulu')->danger()->send();

            return;
        }

        $disk = config('filament.default_filesystem_disk', 'local');
        $path = $this->file->store('imports', ['disk' => $disk]);
        $absolute = Storage::disk($disk)->path($path);

        $reader = new HeadingRowsImport;
        app('excel')->import($reader, $absolute);

        $this->previewRows = $reader->rows
            ->take(15)
            ->map(fn (Collection $row): array => $row->toArray())
            ->values()
            ->all();

        Notification::make()->title('Preview siap')->success()->send();
    }

    public function import(): void
    {
        if (! $this->file) {
            Notification::make()->title('Pilih file dulu')->danger()->send();

            return;
        }

        $modelClass = $this->moduleMap()[$this->module] ?? null;
        if (! $modelClass) {
            Notification::make()->title('Modul tidak valid')->danger()->send();

            return;
        }

        $disk = config('filament.default_filesystem_disk', 'local');
        $path = $this->file->store('imports', ['disk' => $disk]);

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $result = app(ResourceExcelManager::class)->importFromStoredFile(
                modelClass: $modelClass,
                storedPath: $path,
                actor: $user,
                mode: $this->mode,
            );

            Notification::make()
                ->title('Import selesai')
                ->body(sprintf('Dibuat: %d | Diperbarui: %d | Dilewati: %d', $result['created'], $result['updated'], $result['skipped']))
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Import gagal')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
