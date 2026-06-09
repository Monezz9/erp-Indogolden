<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class DashboardInsightWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-insight-widget';

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'lg' => 2,
    ];

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::HeadLogistics->value,
            UserRole::LogisticsAdmin->value,
            UserRole::Branch->value,
        ]);
    }
}
