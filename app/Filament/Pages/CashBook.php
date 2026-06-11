<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Exports\StyledArrayExport;
use App\Filament\Resources\FinanceExpenses\FinanceExpenseResource;
use App\Filament\Resources\FinanceIncomes\FinanceIncomeResource;
use App\Models\Branch;
use App\Models\FinanceExpense;
use App\Models\FinanceIncome;
use App\Models\User;
use App\Support\FinanceBook;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class CashBook extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Buku Kas';

    protected static ?string $title = 'Buku Kas';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.cash-book';

    protected Width | string | null $maxContentWidth = Width::Full;

    public string $search = '';

    public ?int $branchId = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::Admin->value,
            UserRole::Finance->value,
        ]);
    }

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_income')
                ->label('Buat Pemasukan')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->url(FinanceIncomeResource::getUrl('create')),
            Action::make('create_expense')
                ->label('Buat Pengeluaran')
                ->icon('heroicon-o-minus')
                ->color('danger')
                ->url(FinanceExpenseResource::getUrl('create')),
            Action::make('export_cash_book')
                ->label('Export Buku Kas')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): ?BinaryFileResponse => $this->exportCashBook()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getTableRows())
            ->columns([
                TextColumn::make('date_label')->label('Tanggal')->weight('semibold')->width('130px'),
                TextColumn::make('category')->label('Kategori')->badge()->width('150px'),
                TextColumn::make('description')->label('Deskripsi')->wrap()->limit(90),
                TextColumn::make('debit')
                    ->label('Masuk (Debit)')
                    ->alignEnd()
                    ->color('success')
                    ->weight('bold')
                    ->formatStateUsing(fn (mixed $state): string => ((float) $state) > 0 ? FinanceBook::rupiah($state) : '-')
                    ->width('160px'),
                TextColumn::make('credit')
                    ->label('Keluar (Kredit)')
                    ->alignEnd()
                    ->color('danger')
                    ->weight('bold')
                    ->formatStateUsing(fn (mixed $state): string => ((float) $state) > 0 ? FinanceBook::rupiah($state) : '-')
                    ->width('160px'),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->alignEnd()
                    ->weight('bold')
                    ->formatStateUsing(fn (mixed $state): string => FinanceBook::rupiah($state))
                    ->width('160px'),
                TextColumn::make('branch')->label('Cabang')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_method')->label('Kas / Bank / Aplikasi')->badge()->color('info')->toggleable(),
                TextColumn::make('transaction_number')->label('No. Transaksi')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon('heroicon-o-book-open')
            ->emptyStateHeading('Belum ada arus kas')
            ->emptyStateDescription('Pemasukan dan pengeluaran akan tergabung di sini sebagai buku kas harian.');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        return FinanceBook::cashFlowRows(
            search: $this->search !== '' ? $this->search : null,
            branchId: $this->branchId,
            startDate: $this->startDate,
            endDate: $this->endDate,
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function getTableRows(): Collection
    {
        return $this->rows()
            ->map(function (array $row): array {
                $row['__key'] = $row['key'];

                return $row;
            });
    }

    public function summary(): array
    {
        $rows = $this->rows();

        $income = (float) $rows->sum('debit');
        $expense = (float) $rows->sum('credit');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'cash' => (float) $rows
                ->filter(fn (array $row): bool => $row['payment_method'] === 'Tunai')
                ->sum(fn (array $row): float => (float) $row['debit'] - (float) $row['credit']),
            'bank' => (float) $rows
                ->filter(fn (array $row): bool => in_array($row['payment_method'], ['Transfer Bank', 'Debit'], true))
                ->sum(fn (array $row): float => (float) $row['debit'] - (float) $row['credit']),
            'application' => (float) $rows
                ->filter(fn (array $row): bool => $row['payment_method'] === 'QRIS')
                ->sum(fn (array $row): float => (float) $row['debit'] - (float) $row['credit']),
        ];
    }

    public function todaySummary(): array
    {
        $today = now()->toDateString();
        $todayRows = $this->rows()
            ->filter(fn (array $row): bool => $row['transaction_date']?->toDateString() === $today);

        $income = (float) $todayRows->sum('debit');
        $expense = (float) $todayRows->sum('credit');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    public function monthSummary(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $monthRows = $this->rows()
            ->filter(fn (array $row): bool => $row['transaction_date'] && $row['transaction_date']->between($start, $end));

        $income = (float) $monthRows->sum('debit');
        $expense = (float) $monthRows->sum('credit');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'count' => $monthRows->count(),
        ];
    }

    public function latestExpenses(): Collection
    {
        return FinanceBook::scopeForUser(FinanceExpense::query())
            ->with(['category', 'branch'])
            ->when($this->branchId, fn ($query) => $query->where('branch_id', $this->branchId))
            ->when($this->startDate, fn ($query) => $query->whereDate('transaction_date', '>=', $this->startDate))
            ->when($this->endDate, fn ($query) => $query->whereDate('transaction_date', '<=', $this->endDate))
            ->latest('transaction_date')
            ->limit(5)
            ->get();
    }

    public function sevenDayFlow(): Collection
    {
        $rows = $this->rows();
        $endDate = $this->endDate ? Carbon::parse($this->endDate) : Carbon::today();
        $startDate = $this->startDate ? Carbon::parse($this->startDate) : $endDate->copy()->subDays(6);
        $chartStart = $endDate->copy()->subDays(6);

        if ($startDate->greaterThan($chartStart)) {
            $chartStart = $startDate;
        }

        $days = max(0, (int) $chartStart->diffInDays($endDate));

        return collect(range($days, 0))
            ->map(function (int $daysAgo) use ($rows, $endDate): array {
                $date = $endDate->copy()->subDays($daysAgo);
                $dateRows = $rows->filter(fn (array $row): bool => $row['transaction_date']?->toDateString() === $date->toDateString());
                $income = (float) $dateRows->sum('debit');
                $expense = (float) $dateRows->sum('credit');

                return [
                    'label' => $date->format('d M'),
                    'income' => $income,
                    'expense' => $expense,
                    'max' => max($income, $expense, 1),
                ];
            });
    }

    public function groupedRows(): Collection
    {
        return $this->rows()
            ->sortByDesc('transaction_date')
            ->groupBy(fn (array $row): string => $row['transaction_date']?->format('d M Y') ?? 'Tanpa Tanggal');
    }

    /**
     * @return array<int, string>
     */
    public function getBranchOptions(): array
    {
        return Branch::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function isBranchUser(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isBranchLike();
    }

    public function updatedSearch(): void
    {
        $this->resetTable();
    }

    public function updatedBranchId(): void
    {
        $this->resetTable();
    }

    public function updatedStartDate(): void
    {
        $this->resetTable();
    }

    public function updatedEndDate(): void
    {
        $this->resetTable();
    }

    protected function exportCashBook(): ?BinaryFileResponse
    {
        try {
            if (! app()->bound('excel')) {
                throw new \RuntimeException('Paket Excel belum aktif. Jalankan composer dump-autoload lalu refresh browser.');
            }

            $rows = $this->rows()
                ->map(fn (array $row): array => [
                    'tanggal' => $row['date_label'],
                    'kategori' => $row['category'],
                    'deskripsi' => $row['description'],
                    'masuk_debit' => $row['debit'],
                    'keluar_kredit' => $row['credit'],
                    'saldo' => $row['balance'],
                    'cabang' => $row['branch'],
                    'kas_bank_aplikasi' => $row['payment_method'],
                    'no_transaksi' => $row['transaction_number'],
                ]);

            return app('excel')->download(
                new StyledArrayExport(
                    rows: $rows,
                    columns: ['tanggal', 'kategori', 'deskripsi', 'masuk_debit', 'keluar_kredit', 'saldo', 'cabang', 'kas_bank_aplikasi', 'no_transaksi'],
                    labels: [
                        'tanggal' => 'Tanggal',
                        'kategori' => 'Kategori',
                        'deskripsi' => 'Deskripsi',
                        'masuk_debit' => 'Masuk (Debit)',
                        'keluar_kredit' => 'Keluar (Kredit)',
                        'saldo' => 'Saldo',
                        'cabang' => 'Cabang',
                        'kas_bank_aplikasi' => 'Kas / Bank / Aplikasi',
                        'no_transaksi' => 'No. Transaksi',
                    ],
                ),
                'buku_kas_'.now()->format('Ymd_His').'.xlsx',
            );
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Export gagal')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }
}
