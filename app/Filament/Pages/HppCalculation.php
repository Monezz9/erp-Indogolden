<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\HppCalculation as HppCalculationModel;
use App\Models\HppCalculationLine;
use App\Models\User;
use App\Services\HppCalculatorService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class HppCalculation extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static \UnitEnum|string|null $navigationGroup = 'Analisis Operasional';

    protected static ?string $navigationLabel = 'Kalkulasi HPP';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.hpp-calculation';

    public string $productName = 'Pasta Kencur + Baput';

    public float $hargaBeli = 50000;

    public float $stokAwal = 29.95;

    public float $stokMasuk = 20;

    public float $stokKeluar = 49.95;

    public float $hasilCuci = 49.40;

    public float $hasilSelep = 29.80;

    public float $biayaMinyak = 3900;

    public float $biayaGas = 700;

    public float $biayaSelep = 4000;

    public float $biayaPackaging = 0;

    public float $biayaTenagaKerja = 0;

    public float $biayaOverhead = 0;

    public float $hasilProduksi = 28500;

    public float $stokMasukFg = 72;

    public float $mutasiKeluarFg = 37;

    public float $hargaJual = 100000;

    public array $calc = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasAnyRole([
                UserRole::Admin->value,
                UserRole::Gudang->value,
                UserRole::Owner->value,
                UserRole::HeadLogistics->value,
                UserRole::LogisticsAdmin->value,
                UserRole::Finance->value,
            ]);
    }

    public function mount(): void
    {
        $this->recalculate();
    }

    public function updated($name, $value): void
    {
        $this->recalculate();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save_snapshot')
                ->label('Simpan Snapshot HPP')
                ->icon('heroicon-o-bookmark')
                ->color('danger')
                ->action(fn () => $this->saveSnapshot()),
        ];
    }

    public function recalculate(): void
    {
        $svc = app(HppCalculatorService::class);

        $totalStockRaw = $svc->calculateTotalStock($this->stokAwal, $this->stokMasuk);
        $nilaiTotalRaw = $svc->calculateStockValue($totalStockRaw, $this->hargaBeli);
        $nilaiKeluarRaw = $svc->calculateStockValue($this->stokKeluar, $this->hargaBeli);
        $sisaRaw = $svc->calculateRemainingStock($totalStockRaw, $this->stokKeluar);
        $nilaiSisaRaw = $svc->calculateStockValue($sisaRaw, $this->hargaBeli);

        $cleanLoss = $svc->calculateCleaningLoss($this->stokKeluar, $this->hasilSelep);
        $cleanLossPct = $svc->calculateCleaningLossPercent($this->stokKeluar, $this->hasilSelep);
        $cleanHpp = $svc->calculateCleanHPP($nilaiKeluarRaw, $this->hasilSelep);

        $materialCost = $this->hasilSelep * $cleanHpp;
        $totalProductionCost = $svc->calculateProductionCost($materialCost, [
            $this->biayaMinyak,
            $this->biayaGas,
            $this->biayaSelep,
            $this->biayaPackaging,
            $this->biayaTenagaKerja,
            $this->biayaOverhead,
        ]);
        $hppPerUnit = $svc->calculateHPPPerUnit($totalProductionCost, $this->hasilProduksi);

        $totalStokFg = $svc->calculateTotalStock(0, $this->stokMasukFg);
        $nilaiStokFg = $svc->calculateStockValue($totalStokFg, $hppPerUnit);
        $nilaiMutasiFg = $svc->calculateStockValue($this->mutasiKeluarFg, $hppPerUnit);
        $sisaFg = $svc->calculateRemainingStock($totalStokFg, $this->mutasiKeluarFg);
        $nilaiSisaFg = $svc->calculateStockValue($sisaFg, $hppPerUnit);

        $margin = $svc->calculateMargin($this->hargaJual, $hppPerUnit);

        $this->calc = [
            'total_stock_raw' => $totalStockRaw,
            'nilai_total_raw' => $nilaiTotalRaw,
            'nilai_keluar_raw' => $nilaiKeluarRaw,
            'sisa_raw' => $sisaRaw,
            'nilai_sisa_raw' => $nilaiSisaRaw,
            'clean_loss' => $cleanLoss,
            'clean_loss_pct' => $cleanLossPct,
            'clean_hpp' => $cleanHpp,
            'material_cost' => $materialCost,
            'total_production_cost' => $totalProductionCost,
            'hpp_per_unit' => $hppPerUnit,
            'total_stok_fg' => $totalStokFg,
            'nilai_stok_fg' => $nilaiStokFg,
            'nilai_mutasi_fg' => $nilaiMutasiFg,
            'sisa_fg' => $sisaFg,
            'nilai_sisa_fg' => $nilaiSisaFg,
            'profit' => $margin['profit'],
            'margin_pct' => $margin['margin'],
            'warning' => $this->hargaJual > 0 && $this->hargaJual < $hppPerUnit,
        ];
    }

    public function saveSnapshot(): void
    {
        if ($this->hasilProduksi <= 0) {
            Notification::make()->title('Hasil produksi tidak boleh 0')->danger()->send();

            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $calc = HppCalculationModel::query()->create([
            'calc_number' => 'HPP-'.now()->format('YmdHis'),
            'calc_date' => now()->toDateString(),
            'branch_id' => $user->branch_id,
            'created_by' => $user->id,
            'stage' => 'finish_goods',
            'product_name' => $this->productName,
            'total_raw_value' => $this->calc['nilai_total_raw'] ?? 0,
            'total_clean_value' => $this->calc['material_cost'] ?? 0,
            'total_production_cost' => $this->calc['total_production_cost'] ?? 0,
            'hpp_per_unit' => $this->calc['hpp_per_unit'] ?? 0,
            'selling_price' => $this->hargaJual,
            'profit' => $this->calc['profit'] ?? 0,
            'margin_percent' => $this->calc['margin_pct'] ?? 0,
            'notes' => 'Snapshot dari halaman kalkulasi HPP',
        ]);

        HppCalculationLine::query()->create([
            'hpp_calculation_id' => $calc->id,
            'line_type' => 'raw_material',
            'item_name' => 'Ringkasan Bahan Mentah',
            'purchase_price' => $this->hargaBeli,
            'stock_awal' => $this->stokAwal,
            'stock_masuk' => $this->stokMasuk,
            'stock_keluar' => $this->stokKeluar,
            'hasil_bersih' => $this->hasilSelep,
            'additional_cost' => $this->biayaMinyak + $this->biayaGas + $this->biayaSelep + $this->biayaPackaging + $this->biayaTenagaKerja + $this->biayaOverhead,
            'total_value' => $this->calc['total_production_cost'] ?? 0,
            'hpp_result' => $this->calc['hpp_per_unit'] ?? 0,
        ]);

        Notification::make()->title('Snapshot HPP tersimpan')->success()->send();
    }
}
