<?php

namespace App\Enums;

enum ShipmentBatchStatus: string
{
    case Draft = 'draft';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Packed->value => 'Sudah Dipacking',
            self::Shipped->value => 'Sudah Dikirim',
            self::Completed->value => 'Selesai',
            self::Cancelled->value => 'Dibatalkan',
        ];
    }
}
