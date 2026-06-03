<?php

namespace App\Enums;

enum MovementType: string
{
    case InboundPurchase = 'inbound_purchase';
    case CleaningConversion = 'cleaning_conversion';
    case WorkInProcess = 'work_in_process';
    case ProductionConsumption = 'production_consumption';
    case ProductionOutput = 'production_output';
    case WarehouseTransfer = 'warehouse_transfer';
    case BranchTransfer = 'branch_transfer';
    case BranchReceive = 'branch_receive';
    case BranchSale = 'branch_sale';
    case StockAdjustment = 'stock_adjustment';
    case WasteShrinkage = 'waste_shrinkage';
    case StockOpname = 'stock_opname';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::InboundPurchase->value => 'Barang Masuk Pembelian',
            self::CleaningConversion->value => 'Cleaning / Sorting',
            self::WorkInProcess->value => 'Work In Process',
            self::ProductionConsumption->value => 'Pemakaian Produksi',
            self::ProductionOutput->value => 'Hasil Produksi',
            self::WarehouseTransfer->value => 'Transfer Gudang',
            self::BranchTransfer->value => 'Transfer Cabang',
            self::BranchReceive->value => 'Terima Cabang',
            self::BranchSale->value => 'Penjualan Cabang',
            self::StockAdjustment->value => 'Penyesuaian Stok',
            self::WasteShrinkage->value => 'Waste / Susut',
            self::StockOpname->value => 'Stock Opname',
        ];
    }
}
