<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Branches\BranchResource;
use App\Filament\Resources\BranchRequests\BranchRequestResource;
use App\Filament\Resources\BranchSales\BranchSaleResource;
use App\Filament\Resources\FinanceExpenses\FinanceExpenseResource;
use App\Filament\Resources\FinanceIncomes\FinanceIncomeResource;
use App\Filament\Resources\HppCalculations\HppCalculationResource;
use App\Filament\Resources\ItemCategories\ItemCategoryResource;
use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\ProductionOrders\ProductionOrderResource;
use App\Filament\Resources\ProductionRecipes\ProductionRecipeResource;
use App\Filament\Resources\StockBalances\StockBalanceResource;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Warehouses\WarehouseResource;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class OperationalLauncher extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected string $view = 'filament.pages.operational-launcher';

    protected static ?string $slug = 'menu-operasional';

    protected static ?string $navigationLabel = 'Menu Operasional';

    protected static \UnitEnum|string|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 0;

    public function getTitle(): string
    {
        return 'Menu Operasional ERP';
    }

    /**
     * @return array<string, array{label: string, icon: string, accent: string, subtitle: string, items: array<int, array{title: string, description: string, icon: string, url: string, action: string}>}>
     */
    public function menus(): array
    {
        $user = Auth::user();
        $showPos = $user instanceof User && ($user->isAdminLike() || $user->isBranchLike());

        return [
            'master-data' => [
                'label' => 'Master Data',
                'icon' => 'heroicon-o-circle-stack',
                'accent' => 'rose',
                'subtitle' => 'Data dasar barang, cabang, supplier, gudang, satuan, dan kategori.',
                'items' => [
                    $this->item('Barang', 'Kelola SKU, tipe barang, satuan default, dan status aktif.', 'heroicon-o-cube', ItemResource::getUrl(), 'Buka Barang'),
                    $this->item('Supplier', 'Daftar supplier untuk proses pengadaan barang.', 'heroicon-o-building-office-2', SupplierResource::getUrl(), 'Buka Supplier'),
                    $this->item('Gudang', 'Data gudang pusat dan lokasi penyimpanan barang.', 'heroicon-o-home-modern', WarehouseResource::getUrl(), 'Buka Gudang'),
                    $this->item('Cabang', 'Data outlet/cabang yang melakukan request dan penjualan.', 'heroicon-o-building-storefront', BranchResource::getUrl(), 'Buka Cabang'),
                    $this->item('Satuan', 'Satuan operasional untuk item dan transaksi stok.', 'heroicon-o-scale', UnitResource::getUrl(), 'Buka Satuan'),
                    $this->item('Kategori Barang', 'Kelompokkan barang agar laporan dan pencarian lebih rapi.', 'heroicon-o-tag', ItemCategoryResource::getUrl(), 'Buka Kategori'),
                ],
            ],
            'pengadaan' => [
                'label' => 'Pengadaan',
                'icon' => 'heroicon-o-shopping-bag',
                'accent' => 'amber',
                'subtitle' => 'Alur request, review finance, penerimaan barang, dan data supplier.',
                'items' => [
                    $this->item('Permintaan Pengadaan', 'Buat draft PO dan ajukan kebutuhan pembelian barang.', 'heroicon-o-clipboard-document-list', ProcurementRequestWorkspace::getUrl(), 'Buat Permintaan'),
                    $this->item('Review Pengadaan', 'Finance memeriksa, menyetujui, atau menolak pengadaan.', 'heroicon-o-banknotes', ProcurementFinanceReviewWorkspace::getUrl(), 'Review PO'),
                    $this->item('Barang Datang', 'Terima barang dari PO dan catat inbound purchase.', 'heroicon-o-inbox-arrow-down', GoodsReceiveWorkspace::getUrl(), 'Terima Barang'),
                    $this->item('Supplier', 'Buka daftar supplier untuk referensi pembelian.', 'heroicon-o-building-office-2', SupplierResource::getUrl(), 'Buka Supplier'),
                ],
            ],
            'gudang' => [
                'label' => 'Gudang',
                'icon' => 'heroicon-o-archive-box',
                'accent' => 'red',
                'subtitle' => 'Ruang kerja gudang untuk request masuk, packing, pengiriman, dan stok.',
                'items' => [
                    $this->item('Request Masuk', 'Review kebutuhan cabang dan qty yang akan disetujui.', 'heroicon-o-inbox-stack', WarehouseRequestInbox::getUrl(), 'Buka Inbox'),
                    $this->item('Packing Hari Ini', 'Pantau request yang perlu dipacking dan disiapkan.', 'heroicon-o-archive-box-arrow-down', WarehouseRequestInbox::getUrl(), 'Mulai Packing'),
                    $this->item('Pengiriman', 'Lihat jadwal pengiriman dan distribusi ke cabang.', 'heroicon-o-truck', TomorrowShipments::getUrl(), 'Buka Pengiriman'),
                    $this->item('Stok Saat Ini', 'Pantau saldo stok per barang dan gudang.', 'heroicon-o-chart-bar-square', StockBalanceResource::getUrl(), 'Cek Stok'),
                    $this->item('Pergerakan Stok', 'Audit mutasi barang masuk, keluar, dan penyesuaian.', 'heroicon-o-arrows-right-left', StockMovementResource::getUrl(), 'Lihat Mutasi'),
                ],
            ],
            'produksi' => [
                'label' => 'Produksi',
                'icon' => 'heroicon-o-cog-6-tooth',
                'accent' => 'slate',
                'subtitle' => 'Kontrol order produksi, resep, HPP, dan flow material.',
                'items' => [
                    $this->item('Perintah Produksi', 'Kelola order produksi dari bahan sampai hasil jadi.', 'heroicon-o-clipboard-document-check', ProductionOrderResource::getUrl(), 'Buka Order'),
                    $this->item('Resep Produksi', 'Atur komposisi bahan untuk proses produksi.', 'heroicon-o-beaker', ProductionRecipeResource::getUrl(), 'Buka Resep'),
                    $this->item('HPP', 'Hitung dan cek snapshot harga pokok produksi.', 'heroicon-o-calculator', HppCalculation::getUrl(), 'Hitung HPP'),
                    $this->item('Flow Material', 'Lihat alur bahan mentah, WIP, dan barang jadi.', 'heroicon-o-arrows-right-left', LogisticsWorkflow::getUrl(), 'Lihat Flow'),
                    $this->item('Snapshot HPP', 'Buka arsip kalkulasi HPP yang sudah tersimpan.', 'heroicon-o-document-chart-bar', HppCalculationResource::getUrl(), 'Buka Snapshot'),
                ],
            ],
            'cabang' => [
                'label' => 'Cabang',
                'icon' => 'heroicon-o-building-storefront',
                'accent' => 'emerald',
                'subtitle' => 'Aktivitas cabang untuk permintaan barang, penjualan, dan stok harian.',
                'items' => [
                    ...($showPos ? [
                        $this->item('POS Kasir', 'Input penjualan kasir dan posting langsung ke stok serta finance.', 'heroicon-o-computer-desktop', CashierPos::getUrl(), 'Buka POS'),
                    ] : []),
                    $this->item('Permintaan Barang', 'Cabang membuat request barang untuk dikirim gudang.', 'heroicon-o-clipboard-document-list', BranchRequestResource::getUrl(), 'Buat Request'),
                    $this->item('Penjualan Cabang', 'Input dan pantau transaksi penjualan cabang.', 'heroicon-o-shopping-cart', BranchSaleResource::getUrl(), 'Buka Penjualan'),
                    $this->item('Stok Harian', 'Laporan stok harian cabang untuk kontrol operasional.', 'heroicon-o-chart-bar', BranchDailyStockReport::getUrl(), 'Buka Laporan'),
                    $this->item('Pengiriman Besok', 'Pantau barang yang dijadwalkan dikirim ke cabang.', 'heroicon-o-truck', TomorrowShipments::getUrl(), 'Cek Kiriman'),
                ],
            ],
            'finance' => [
                'label' => 'Finance',
                'icon' => 'heroicon-o-banknotes',
                'accent' => 'sky',
                'subtitle' => 'Pencatatan pemasukan, pengeluaran, dan review pengadaan.',
                'items' => [
                    $this->item('Pengeluaran', 'Catat biaya operasional dan transaksi keluar.', 'heroicon-o-arrow-trending-down', FinanceExpenseResource::getUrl(), 'Buka Pengeluaran'),
                    $this->item('Pemasukan', 'Catat pemasukan dan transaksi masuk.', 'heroicon-o-arrow-trending-up', FinanceIncomeResource::getUrl(), 'Buka Pemasukan'),
                    $this->item('Review Pengadaan', 'Validasi pengadaan sebelum proses penerimaan barang.', 'heroicon-o-banknotes', ProcurementFinanceReviewWorkspace::getUrl(), 'Review PO'),
                    $this->item('HPP', 'Cek biaya produksi untuk kebutuhan kontrol margin.', 'heroicon-o-calculator', HppCalculation::getUrl(), 'Cek HPP'),
                ],
            ],
            'laporan' => [
                'label' => 'Laporan',
                'icon' => 'heroicon-o-presentation-chart-line',
                'accent' => 'violet',
                'subtitle' => 'Shortcut laporan operasional, audit stok, import, dan dashboard.',
                'items' => [
                    $this->item('Dasbor', 'Ringkasan KPI dan aktivitas penting sistem.', 'heroicon-o-home', Dashboard::getUrl(), 'Buka Dasbor'),
                    $this->item('Laporan Stok Harian', 'Pantau stok cabang per tanggal operasional.', 'heroicon-o-chart-bar', BranchDailyStockReport::getUrl(), 'Buka Laporan'),
                    $this->item('Alur Logistik', 'Presentasi visual alur stok, produksi, dan distribusi.', 'heroicon-o-arrows-right-left', LogisticsWorkflow::getUrl(), 'Buka Alur'),
                    $this->item('Import Data', 'Masuk ke workspace import dan validasi data.', 'heroicon-o-arrow-up-on-square-stack', GlobalDataImport::getUrl(), 'Buka Import'),
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, icon: string, url: string, action: string}
     */
    private function item(string $title, string $description, string $icon, string $url, string $action): array
    {
        return compact('title', 'description', 'icon', 'url', 'action');
    }
}
