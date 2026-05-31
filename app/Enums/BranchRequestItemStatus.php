<?php

namespace App\Enums;

enum BranchRequestItemStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Partial = 'partial';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case Received = 'received';
    case OutOfStock = 'out_of_stock';
    case Substituted = 'substituted';
    case Rejected = 'rejected';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Requested->value => 'Diminta',
            self::Approved->value => 'Disetujui',
            self::Partial->value => 'Sebagian',
            self::Packed->value => 'Sudah Dipacking',
            self::Shipped->value => 'Sudah Dikirim',
            self::Received->value => 'Sudah Diterima',
            self::OutOfStock->value => 'Stok Kosong',
            self::Substituted->value => 'Diganti Item Lain',
            self::Rejected->value => 'Ditolak',
        ];
    }
}
