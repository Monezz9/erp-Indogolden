<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class InventoryStockStatus
{
    public const SAFE = 'Aman';

    public const ATTENTION = 'Perhatian';

    public const CRITICAL = 'Kritis';

    public static function status(float $stock, float $minimum): string
    {
        if ($minimum <= 0) {
            return self::SAFE;
        }

        if ($stock <= 0 || $stock < $minimum) {
            return self::CRITICAL;
        }

        if ($stock < ($minimum * 1.5)) {
            return self::ATTENTION;
        }

        return self::SAFE;
    }

    public static function progress(float $stock, float $minimum): int
    {
        if ($minimum <= 0) {
            return $stock > 0 ? 100 : 0;
        }

        return (int) min(100, round(($stock / max($minimum * 1.5, 1)) * 100));
    }

    public static function color(string $status): string
    {
        return match ($status) {
            self::CRITICAL => 'danger',
            self::ATTENTION => 'warning',
            default => 'success',
        };
    }

    public static function applyFilter(Builder $query, ?string $status, string $stockExpression): Builder
    {
        return match ($status) {
            'safe' => $query->where(fn (Builder $query): Builder => $query
                ->where('minimum_stock', '<=', 0)
                ->orWhereRaw("$stockExpression >= (items.minimum_stock * 1.5)")),
            'attention' => $query
                ->where('minimum_stock', '>', 0)
                ->whereRaw("$stockExpression >= items.minimum_stock")
                ->whereRaw("$stockExpression < (items.minimum_stock * 1.5)"),
            'critical' => $query
                ->where('minimum_stock', '>', 0)
                ->where(fn (Builder $query): Builder => $query
                    ->whereRaw("$stockExpression <= 0")
                    ->orWhereRaw("$stockExpression < items.minimum_stock")),
            default => $query,
        };
    }
}
