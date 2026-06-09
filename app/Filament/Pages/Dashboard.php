<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Widgets\DashboardInsightWidget;
use App\Filament\Widgets\FinanceTrendChart;
use App\Filament\Widgets\InventoryMovementChart;
use App\Filament\Widgets\KpiOverview;
use App\Filament\Widgets\LowStockItemsTable;
use App\Filament\Widgets\PendingApprovalsOverview;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dasbor';

    public function getTitle(): string
    {
        return 'Ringkasan Operasional';
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.partials.dashboard-header');
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 6,
            'lg' => 6,
        ];
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return ['fi-page-dashboard'];
    }

    public function getWidgets(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        if ($user->hasRole(UserRole::Finance->value)) {
            return [
                KpiOverview::class,
                FinanceTrendChart::class,
            ];
        }

        if ($user->isBranchLike()) {
            return [
                KpiOverview::class,
                InventoryMovementChart::class,
                DashboardInsightWidget::class,
                LowStockItemsTable::class,
            ];
        }

        $widgets = [
            KpiOverview::class,
            InventoryMovementChart::class,
            DashboardInsightWidget::class,
            LowStockItemsTable::class,
        ];

        if ($user->isAdminLike() || $user->hasAnyRole([UserRole::HeadLogistics->value, UserRole::Gudang->value])) {
            return [
                PendingApprovalsOverview::class,
                ...$widgets,
            ];
        }

        return $widgets;
    }
}
