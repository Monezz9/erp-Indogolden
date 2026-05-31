<?php

namespace App\Support;

class InventoryLabels
{
    public static function stage(?string $code, ?string $fallback = null): string
    {
        return [
            'raw_dirty' => 'RM - Bahan Mentah',
            'raw_clean' => 'SRM - Bahan Sortir',
            'wip' => 'WIP - Proses Produksi',
            'finished_goods' => 'FG - Barang Jadi',
            'branch_stock' => 'Stok Cabang',
            'mro' => 'Barang Operasional',
            'analysis' => 'Item Analisis',
        ][$code ?? ''] ?? ($fallback ?: '-');
    }

    public static function categoryType(?string $type): string
    {
        return [
            'raw_material' => 'Bahan / Material',
            'wip' => 'Proses Produksi',
            'finished_goods' => 'Barang Jadi',
            'mro' => 'Operasional',
            'analysis' => 'Analisis',
            'other' => 'Lainnya',
        ][$type ?? ''] ?? ($type ?: '-');
    }

    public static function itemType(?string $type): string
    {
        return [
            'material' => 'Barang',
            'semi_finished' => 'Setengah Jadi',
            'product' => 'Produk Jadi',
            'packaging' => 'Kemasan',
            'service' => 'Jasa',
        ][$type ?? ''] ?? ($type ?: '-');
    }
}
