<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\StockMovementItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Widgets\ChartWidget;

class InventoryMovementChart extends ChartWidget
{
    protected ?string $heading = 'Pergerakan Barang Mingguan';

    protected ?string $description = 'Melihat ritme barang masuk dan keluar dari gudang.';

    protected ?string $maxHeight = '380px';

    protected int | string | array $columnSpan = [
        'md' => 4,
        'lg' => 4,
    ];

    protected static bool $isLazy = true;

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

    protected function getData(): array
    {
        $user = Auth::user();
        $branchId = ($user instanceof User && $user->isBranchLike()) ? $user->branch_id : null;

        $startDate = Carbon::today()->subDays(6)->startOfDay();
        $endDate = Carbon::today()->endOfDay();

        $movementRows = StockMovementItem::query()
            ->selectRaw("DATE(created_at) as movement_date, direction, SUM(qty) as total_qty")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn (Builder $query) => $query->where(function (Builder $movementQuery) use ($branchId) {
                $movementQuery
                    ->where('from_branch_id', $branchId)
                    ->orWhere('to_branch_id', $branchId);
            }))
            ->whereIn('direction', ['in', 'out'])
            ->groupByRaw('DATE(created_at), direction')
            ->get();

        /** @var Collection<string, Collection<string, object>> $movementByDate */
        $movementByDate = $movementRows->groupBy(fn (object $row): string => (string) $row->movement_date)
            ->map(fn (Collection $rows): Collection => $rows->keyBy('direction'));

        $labels = [];
        $in = [];
        $out = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateKey = $date->toDateString();
            $daily = $movementByDate->get($dateKey);

            $labels[] = $date->format('d M');
            $in[] = (float) ($daily?->get('in')->total_qty ?? 0);
            $out[] = (float) ($daily?->get('out')->total_qty ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Masuk',
                    'data' => $in,
                    'backgroundColor' => '#d99018',
                    'borderRadius' => 8,
                ],
                [
                    'label' => 'Keluar',
                    'data' => $out,
                    'backgroundColor' => '#334155',
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 Hari Terakhir',
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 10,
                        'boxHeight' => 10,
                        'useBorderRadius' => true,
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.18)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
