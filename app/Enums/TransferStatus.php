<?php

namespace App\Enums;

enum TransferStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Shipped = 'shipped';
    case Received = 'received';
    case Rejected = 'rejected';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Submitted->value => 'Diajukan',
            self::Approved->value => 'Disetujui',
            self::Shipped->value => 'Sudah Dikirim',
            self::Received->value => 'Sudah Diterima',
            self::Rejected->value => 'Ditolak',
        ];
    }
}
