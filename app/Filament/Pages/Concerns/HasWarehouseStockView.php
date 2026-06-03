<?php

namespace App\Filament\Pages\Concerns;

use App\Enums\UserRole;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait HasWarehouseStockView
{
    public string $stageFilter = 'all';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->isAdminLike()
            || $user->isWarehouseLike()
            || $user->hasRole(UserRole::Finance->value)
            || $user->isBranchLike()
        );
    }

    /**
     * @return Collection<int, StockBalance>
     */
    public function rows(): Collection
    {
        $stageFilter = static::fixedStageFilter() ?? $this->stageFilter;

        return StockBalance::query()
            ->with(['item.defaultUnit', 'stage', 'warehouse', 'branch'])
            ->where('qty_on_hand', '>', 0)
            ->whereHas('warehouse', fn (Builder $query) => $this->applyWarehouseScope($query))
            ->when($stageFilter !== 'all', fn (Builder $query) => $query->whereHas(
                'stage',
                fn (Builder $stage) => $stage->where('code', $stageFilter),
            ))
            ->orderByRaw('COALESCE((select sequence from item_stages where item_stages.id = stock_balances.stage_id), 999)')
            ->orderBy(
                \App\Models\Item::query()
                    ->select('name')
                    ->whereColumn('items.id', 'stock_balances.item_id')
                    ->limit(1),
            )
            ->get();
    }

    public function warehouseName(): string
    {
        return Warehouse::query()
            ->where(fn (Builder $query) => $this->applyWarehouseScope($query))
            ->value('name') ?? static::warehouseLabel();
    }

    public function stageOptions(): array
    {
        if ($fixedStage = static::fixedStageFilter()) {
            return StockBalance::query()
                ->whereHas('warehouse', fn (Builder $query) => $this->applyWarehouseScope($query))
                ->whereHas('stage', fn (Builder $query) => $query->where('code', $fixedStage))
                ->with('stage')
                ->get()
                ->pluck('stage.name', 'stage.code')
                ->filter()
                ->all();
        }

        return ['all' => 'Semua Tahap'] + StockBalance::query()
            ->whereHas('warehouse', fn (Builder $query) => $this->applyWarehouseScope($query))
            ->whereHas('stage')
            ->with('stage')
            ->get()
            ->pluck('stage.name', 'stage.code')
            ->filter()
            ->all();
    }

    public function totalQty(): float
    {
        return (float) $this->rows()->sum('qty_on_hand');
    }

    public function totalValue(): float
    {
        return (float) $this->rows()->sum('total_value');
    }

    protected function applyWarehouseScope(Builder $query): Builder
    {
        return $query->whereIn('code', static::warehouseCodes())
            ->orWhereIn('name', static::warehouseNames());
    }

    /**
     * @return list<string>
     */
    protected static function warehouseCodes(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    protected static function warehouseNames(): array
    {
        return [];
    }

    protected static function fixedStageFilter(): ?string
    {
        return null;
    }

    protected static function warehouseLabel(): string
    {
        return static::$title ?? 'Gudang';
    }
}
