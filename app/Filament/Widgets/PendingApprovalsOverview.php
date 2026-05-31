<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\ProductionOrder;
use App\Models\StockMovement;
use App\Models\Transfer;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PendingApprovalsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::HeadLogistics->value,
        ]);
    }

    protected function getStats(): array
    {
        $movement = StockMovement::query()->where('status', 'submitted')->count();
        $transfer = Transfer::query()->where('status', 'submitted')->count();
        $production = ProductionOrder::query()->where('status', 'submitted')->count();

        return [
            Stat::make('Pending Stock Movement', (string) $movement)->color('warning'),
            Stat::make('Pending Transfer', (string) $transfer)->color('warning'),
            Stat::make('Pending Production', (string) $production)->color('warning'),
        ];
    }
}
