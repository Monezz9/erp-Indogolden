<?php

namespace App\Filament\Resources\FinanceIncomes\Schemas;

use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\FinanceCategory;
use App\Support\FinanceBook;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class FinanceIncomeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'lg' => 3,
            ])
            ->components([
                Grid::make(1)
                    ->schema([
                        Section::make('Informasi Transaksi')
                            ->description('Identitas transaksi kas masuk.')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TextInput::make('transaction_number')
                                    ->label('No. Transaksi')
                                    ->required()
                                    ->maxLength(40)
                                    ->unique(ignoreRecord: true)
                                    ->live(onBlur: true),
                                DateTimePicker::make('transaction_date')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now())
                                    ->live(),
                                Select::make('branch_id')
                                    ->label('Cabang')
                                    ->relationship('branch', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                                Select::make('finance_category_id')
                                    ->label('Kategori Pembukuan')
                                    ->relationship('category', 'name', fn ($query) => $query->where('type', 'income'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                            ])
                            ->columns(2),
                        Section::make('Nominal & Pembayaran')
                            ->description('Pilih kanal uang diterima dan nominal pemasukan.')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Nominal')
                                    ->prefix('Rp')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->live(onBlur: true),
                                ToggleButtons::make('payment_method')
                                    ->label('Metode Pembayaran')
                                    ->required()
                                    ->default(PaymentMethod::Cash->value)
                                    ->options([
                                        PaymentMethod::Cash->value => 'Kas',
                                        PaymentMethod::BankTransfer->value => 'Bank',
                                        PaymentMethod::Debit->value => 'Debit',
                                        PaymentMethod::Qris->value => 'QRIS / Aplikasi',
                                        PaymentMethod::Other->value => 'Lainnya',
                                    ])
                                    ->icons([
                                        PaymentMethod::Cash->value => 'heroicon-o-banknotes',
                                        PaymentMethod::BankTransfer->value => 'heroicon-o-building-library',
                                        PaymentMethod::Debit->value => 'heroicon-o-credit-card',
                                        PaymentMethod::Qris->value => 'heroicon-o-device-phone-mobile',
                                        PaymentMethod::Other->value => 'heroicon-o-squares-2x2',
                                    ])
                                    ->colors([
                                        PaymentMethod::Cash->value => 'success',
                                        PaymentMethod::BankTransfer->value => 'info',
                                        PaymentMethod::Debit->value => 'info',
                                        PaymentMethod::Qris->value => 'info',
                                        PaymentMethod::Other->value => 'gray',
                                    ])
                                    ->columns(3)
                                    ->live(),
                            ]),
                        Section::make('Catatan')
                            ->description('Tambahkan konteks singkat agar mudah dibaca di Buku Kas.')
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(5)
                                    ->live(onBlur: true)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan([
                        'lg' => 2,
                    ]),
                Section::make('Preview Transaksi')
                    ->description('Ringkasan yang akan muncul sebagai debit di Buku Kas.')
                    ->icon('heroicon-o-eye')
                    ->extraAttributes(['class' => 'ig-finance-preview ig-finance-preview--income'])
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('preview')
                            ->hiddenLabel()
                            ->content(fn (Get $get): HtmlString => self::preview($get)),
                        \Filament\Forms\Components\Placeholder::make('flow')
                            ->hiddenLabel()
                            ->content(new HtmlString('
                                <div class="ig-finance-flow">
                                    <div class="ig-finance-flow__title">Alur Pencatatan</div>
                                    <ol>
                                        <li>Input pemasukan.</li>
                                        <li>Masuk ke Buku Kas.</li>
                                        <li>Saldo bertambah.</li>
                                        <li>Bisa diexport ke Excel.</li>
                                    </ol>
                                </div>
                            ')),
                    ])
                    ->columnSpan([
                        'lg' => 1,
                    ]),
            ]);
    }

    protected static function preview(Get $get): HtmlString
    {
        $branch = $get('branch_id') ? Branch::query()->find($get('branch_id'))?->name : '-';
        $category = $get('finance_category_id') ? FinanceCategory::query()->find($get('finance_category_id')) : null;
        $date = $get('transaction_date') ? date('d M Y H:i', strtotime((string) $get('transaction_date'))) : '-';
        $notes = trim((string) $get('notes'));

        return new HtmlString(sprintf(
            '<div class="ig-finance-preview-card">
                <span class="ig-finance-badge ig-finance-badge--income">Pemasukan</span>
                <div class="ig-finance-preview-amount ig-finance-preview-amount--income">%s</div>
                <dl>
                    <div><dt>Cabang</dt><dd>%s</dd></div>
                    <div><dt>Kategori</dt><dd>%s</dd></div>
                    <div><dt>Metode Bayar</dt><dd>%s</dd></div>
                    <div><dt>Tanggal</dt><dd>%s</dd></div>
                    <div><dt>Catatan</dt><dd>%s</dd></div>
                </dl>
            </div>',
            e(FinanceBook::rupiah($get('amount') ?: 0)),
            e($branch),
            e(FinanceBook::categoryLabel($category?->code, $category?->name, $category?->type)),
            e(FinanceBook::paymentLabel($get('payment_method') ?: PaymentMethod::Cash->value)),
            e($date),
            e($notes !== '' ? $notes : '-'),
        ));
    }
}
