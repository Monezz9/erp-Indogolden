<?php

namespace App\Enums;

enum ItemStageCode: string
{
    case RawDirty = 'raw_dirty';
    case RawClean = 'raw_clean';
    case Wip = 'wip';
    case FinishedGoods = 'finished_goods';
    case BranchStock = 'branch_stock';
    case Mro = 'mro';
    case Analysis = 'analysis';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::RawDirty->value => 'Raw Dirty',
            self::RawClean->value => 'Raw Clean',
            self::Wip->value => 'WIP',
            self::FinishedGoods->value => 'Finished Goods',
            self::BranchStock->value => 'Branch Stock',
            self::Mro->value => 'MRO',
            self::Analysis->value => 'Analysis',
        ];
    }
}
