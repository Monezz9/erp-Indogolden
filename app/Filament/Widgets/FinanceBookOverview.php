<?php

namespace App\Filament\Widgets;

use App\Support\FinanceBook;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceBookOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Ringkasan Buku Kas';

    protected ?string $description = 'Ikhtisar kas masuk, kas keluar, saldo akhir, dan posisi kanal pembayaran.';

    protected int | array | null $columns = [
        '@xl' => 4,
        '!@lg' => 2,
        '!@sm' => 1,
    ];

    protected function getStats(): array
    {
        $summary = FinanceBook::summary();

        return [
            Stat::make('Total Pemasukan', FinanceBook::rupiah($summary['income']))
                ->description('Akumulasi uang masuk')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Pengeluaran', FinanceBook::rupiah($summary['expense']))
                ->description('Akumulasi uang keluar')
                ->icon('heroicon-o-arrow-trending-down')
                ->color('danger'),
            Stat::make('Saldo Akhir', FinanceBook::rupiah($summary['balance']))
                ->description('Pemasukan dikurangi pengeluaran')
                ->icon('heroicon-o-scale')
                ->color($summary['balance'] >= 0 ? 'success' : 'danger'),
            Stat::make('Kas / Bank / Aplikasi', sprintf(
                '%s / %s / %s',
                FinanceBook::rupiah($summary['cash']),
                FinanceBook::rupiah($summary['bank']),
                FinanceBook::rupiah($summary['application']),
            ))
                ->description('Tunai, transfer/debit, dan QRIS')
                ->icon('heroicon-o-credit-card')
                ->color('info'),
        ];
    }
}
