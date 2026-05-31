<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Exports\StyledArrayExport;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchDailyStockReportService;
use App\Support\IndoNumber;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BranchDailyStockReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static \UnitEnum|string|null $navigationGroup = 'Operasional Cabang';

    protected static ?string $navigationLabel = 'Laporan Stok Harian';

    protected string $view = 'filament.pages.branch-daily-stock-report';

    public string $reportDate;

    public ?int $branchId = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->reportDate = now()->toDateString();

        if ($user instanceof User && $user->isBranchLike()) {
            $this->branchId = $user->branch_id;
        } else {
            $this->branchId = Branch::query()->value('id');
        }
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::Finance->value,
            UserRole::HeadLogistics->value,
            UserRole::LogisticsAdmin->value,
            UserRole::Branch->value,
        ]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): ?BinaryFileResponse {
                    try {
                        if (! app()->bound('excel')) {
                            throw new \RuntimeException('Paket Excel belum aktif. Jalankan composer dump-autoload lalu refresh browser.');
                        }

                        return app('excel')->download(
                            new StyledArrayExport(
                                rows: $this->getReportRows(),
                                columns: ['sku', 'item_name', 'unit', 'opening_qty', 'incoming_qty', 'outgoing_qty', 'closing_qty', 'closing_value'],
                                labels: [
                                    'sku' => 'SKU',
                                    'item_name' => 'Barang',
                                    'unit' => 'Satuan',
                                    'opening_qty' => 'Stok Awal',
                                    'incoming_qty' => 'Masuk Hari Ini',
                                    'outgoing_qty' => 'Keluar Hari Ini',
                                    'closing_qty' => 'Sisa Stok',
                                    'closing_value' => 'Nilai Stok (Rp)',
                                ],
                            ),
                            'laporan_stok_harian_'.now()->format('Ymd_His').'.xlsx',
                        );
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Export gagal')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return null;
                    }
                }),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getReportRows(): Collection
    {
        if (! $this->branchId) {
            return collect();
        }

        return app(BranchDailyStockReportService::class)->daily(
            branchId: $this->branchId,
            date: $this->reportDate,
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getTableRows())
            ->columns([
                TextColumn::make('sku')->label('SKU')->searchable(),
                TextColumn::make('item_name')->label('Barang')->searchable(),
                TextColumn::make('unit')->label('Satuan'),
                TextColumn::make('opening_qty')
                    ->label('Stok Awal')
                    ->alignEnd()
                    ->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
                TextColumn::make('incoming_qty')
                    ->label('Masuk')
                    ->alignEnd()
                    ->color('success')
                    ->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
                TextColumn::make('outgoing_qty')
                    ->label('Keluar')
                    ->alignEnd()
                    ->color('danger')
                    ->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
                TextColumn::make('closing_qty')
                    ->label('Sisa')
                    ->alignEnd()
                    ->weight('bold')
                    ->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
                TextColumn::make('closing_value')
                    ->label('Nilai Stok')
                    ->alignEnd()
                    ->weight('bold')
                    ->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->striped()
            ->emptyStateHeading('Belum ada data pergerakan stok')
            ->emptyStateDescription('Ubah filter tanggal atau cabang untuk melihat detail laporan.');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function getTableRows(): Collection
    {
        return $this->getReportRows()
            ->values()
            ->map(function (array $row, int $index): array {
                $row['__key'] = (string) ($row['item_id'] ?? $index + 1);

                return $row;
            });
    }

    public function updatedReportDate(): void
    {
        $this->resetTable();
    }

    public function updatedBranchId(): void
    {
        $this->resetTable();
    }

    /**
     * @return array<int, string>
     */
    public function getBranchOptions(): array
    {
        return Branch::query()->pluck('name', 'id')->all();
    }

    public function isBranchUser(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isBranchLike();
    }
}
