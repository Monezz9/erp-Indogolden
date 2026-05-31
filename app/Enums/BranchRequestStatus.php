<?php

namespace App\Enums;

enum BranchRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case Received = 'received';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Submitted->value => 'Diajukan',
            self::Reviewed->value => 'Sudah Direview',
            self::Approved->value => 'Disetujui',
            self::Packed->value => 'Sudah Dipacking',
            self::Shipped->value => 'Sudah Dikirim',
            self::Received->value => 'Sudah Diterima',
            self::Rejected->value => 'Ditolak',
            self::Cancelled->value => 'Dibatalkan',
        ];
    }
}
