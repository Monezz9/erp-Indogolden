<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class LogisticsWorkflow extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected string $view = 'filament.pages.logistics-workflow';

    protected static ?string $navigationLabel = 'Alur Logistik';

    protected static \UnitEnum|string|null $navigationGroup = 'Analisis Operasional';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasAnyRole([
                UserRole::Admin->value,
                UserRole::Gudang->value,
                UserRole::Cabang->value,
                UserRole::Owner->value,
                UserRole::HeadLogistics->value,
                UserRole::LogisticsAdmin->value,
                UserRole::Branch->value,
                UserRole::Finance->value,
            ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function workflowStages(): array
    {
        return [
            [
                'id' => 'rm',
                'title' => 'BAHAN MENTAH',
                'code' => 'RM',
                'desc' => 'Bahan baku utama mentah yang belum diolah dan masih kotor.',
                'flow' => 'Grooming',
                'icon' => 'heroicon-o-building-storefront',
                'color' => 'from-red-600 to-red-800',
            ],
            [
                'id' => 'srm',
                'title' => 'BAHAN MENTAH SORTIR',
                'code' => 'SRM',
                'desc' => 'Bahan baku mentah yang sudah melalui proses pembersihan dan siap diproses.',
                'flow' => 'Produksi',
                'icon' => 'heroicon-o-funnel',
                'color' => 'from-rose-600 to-red-800',
            ],
            [
                'id' => 'wip',
                'title' => 'PROSES PRODUKSI',
                'code' => 'WIP',
                'desc' => 'Barang dalam tahap produksi dan belum menjadi produk jadi.',
                'flow' => 'Packaging',
                'icon' => 'heroicon-o-cog-8-tooth',
                'color' => 'from-red-700 to-zinc-800',
            ],
            [
                'id' => 'fg',
                'title' => 'BARANG JADI',
                'code' => 'FG',
                'desc' => 'Produk selesai diproduksi dan siap dijual atau didistribusikan.',
                'flow' => 'Masuk Cabang',
                'icon' => 'heroicon-o-archive-box',
                'color' => 'from-red-600 to-zinc-900',
            ],
            [
                'id' => 'indogolden',
                'title' => 'INDOGOLDEN',
                'code' => 'SYS',
                'desc' => 'Produk jadi siap jual diinput ke sistem untuk distribusi cabang.',
                'flow' => 'Distribusi',
                'icon' => 'heroicon-o-circle-stack',
                'color' => 'from-red-700 to-red-900',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function statSummary(): array
    {
        $totalRaw = Item::query()->whereHas('defaultStage', fn ($q) => $q->where('code', 'raw_dirty'))->count();
        $totalWip = Item::query()->whereHas('defaultStage', fn ($q) => $q->where('code', 'wip'))->count();
        $totalFg = Item::query()->whereHas('defaultStage', fn ($q) => $q->where('code', 'finished_goods'))->count();
        $distributionReady = StockBalance::query()->whereHas('item.defaultStage', fn ($q) => $q->where('code', 'finished_goods'))->sum('qty_on_hand');

        return [
            'total_raw' => $totalRaw,
            'total_wip' => $totalWip,
            'total_fg' => $totalFg,
            'distribution_ready' => $distributionReady,
        ];
    }

    public function rawItems(): array
    {
        return ['Kencur', 'Cabe Keriting', 'Cabe Rawit', 'Pokcoy', 'Jamur Kuping'];
    }

    public function sortedItems(): array
    {
        return ['All Keringan', 'Enoki', 'Kwetiau', 'Minuman', 'Beef Slice', 'Premix', 'All from RM'];
    }

    public function wipItems(): array
    {
        return ['Barang dari Vendor', 'Pasta'];
    }

    public function fgItems(): array
    {
        return ['All Frozen', 'All Keringan', 'All Sayur', 'Paket Minuman'];
    }

    public function mroItems(): array
    {
        return ['Plastik', 'Kresek', 'Tisu'];
    }

    public function analysisItems(): array
    {
        return ['Bumbu (Kuah)', 'Es Teller', 'Gula Cair'];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function stockPreview(string $code): Collection
    {
        return StockBalance::query()
            ->with(['item.defaultStage', 'item.defaultUnit'])
            ->whereHas('item.defaultStage', fn ($q) => $q->where('code', $code))
            ->orderByDesc('qty_on_hand')
            ->limit(8)
            ->get()
            ->map(fn (StockBalance $balance): array => [
                'sku' => (string) ($balance->item?->sku ?? '-'),
                'item' => (string) ($balance->item?->name ?? '-'),
                'qty' => (float) $balance->qty_on_hand,
                'unit' => (string) ($balance->item?->defaultUnit?->name ?? '-'),
                'value' => (float) $balance->total_value,
            ]);
    }
}
