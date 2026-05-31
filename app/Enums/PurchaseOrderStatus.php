<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case FinanceApproved = 'finance_approved';
    case FinanceRejected = 'finance_rejected';
    case Ordered = 'ordered';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Submitted->value => 'Diajukan',
            self::FinanceApproved->value => 'Disetujui Finance',
            self::FinanceRejected->value => 'Ditolak Finance',
            self::Ordered->value => 'Sudah Dipesan',
            self::PartiallyReceived->value => 'Diterima Sebagian',
            self::Received->value => 'Sudah Diterima',
            self::Cancelled->value => 'Dibatalkan',
        ];
    }
}
