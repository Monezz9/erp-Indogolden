<?php

namespace App\Services;

class HppCalculatorService
{
    public function calculateTotalStock(float $stockAwal, float $stockMasuk): float
    {
        return $stockAwal + $stockMasuk;
    }

    public function calculateStockValue(float $totalStock, float $price): float
    {
        return $totalStock * $price;
    }

    public function calculateRemainingStock(float $totalStock, float $stockKeluar): float
    {
        return $totalStock - $stockKeluar;
    }

    public function calculateCleaningLoss(float $stockMasuk, float $hasilBersih): float
    {
        return $stockMasuk - $hasilBersih;
    }

    public function calculateCleaningLossPercent(float $stockMasuk, float $hasilBersih): float
    {
        if ($stockMasuk <= 0) {
            return 0;
        }

        return (($stockMasuk - $hasilBersih) / $stockMasuk) * 100;
    }

    public function calculateCleanHPP(float $totalRawValue, float $hasilBersih): float
    {
        if ($hasilBersih <= 0) {
            return 0;
        }

        return $totalRawValue / $hasilBersih;
    }

    /**
     * @param  array<int, float>  $additionalCosts
     */
    public function calculateProductionCost(float $materialCost, array $additionalCosts = []): float
    {
        return $materialCost + array_sum($additionalCosts);
    }

    public function calculateHPPPerUnit(float $totalProductionCost, float $hasilProduksi): float
    {
        if ($hasilProduksi <= 0) {
            return 0;
        }

        return $totalProductionCost / $hasilProduksi;
    }

    /**
     * @return array{profit:float,margin:float}
     */
    public function calculateMargin(float $sellingPrice, float $hpp): array
    {
        $profit = $sellingPrice - $hpp;

        if ($sellingPrice <= 0) {
            return [
                'profit' => $profit,
                'margin' => 0,
            ];
        }

        return [
            'profit' => $profit,
            'margin' => ($profit / $sellingPrice) * 100,
        ];
    }
}
