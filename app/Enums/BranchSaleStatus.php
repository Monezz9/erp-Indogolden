<?php

namespace App\Enums;

enum BranchSaleStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Cancelled = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Posted->value => 'Diposting',
            self::Cancelled->value => 'Dibatalkan',
        ];
    }
}
