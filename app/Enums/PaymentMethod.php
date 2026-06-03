<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Debit = 'debit';
    case BankTransfer = 'bank_transfer';
    case Qris = 'qris';
    case Other = 'other';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Cash->value => 'Tunai',
            self::Debit->value => 'Debit',
            self::BankTransfer->value => 'Transfer Bank',
            self::Qris->value => 'QRIS',
            self::Other->value => 'Lainnya',
        ];
    }
}
