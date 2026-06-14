<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\Items\Widgets\ItemInventoryOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ListItems extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = ItemResource::class;

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        $excelActions = $this->getExcelHeaderActions();

        foreach ($excelActions as $action) {
            if ($action->getName() === 'import_excel') {
                $action->outlined()->color('warning');
            }

            if ($action->getName() === 'export_excel') {
                $action->outlined()->color('gray');
            }
        }

        return [
            CreateAction::make()
                ->label('Tambah Barang')
                ->icon('heroicon-o-plus')
                ->color('warning'),
            ...$excelActions,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ItemInventoryOverview::class,
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.items.pages.inventory-workspace-header', [
            'actions' => $this->getCachedHeaderActions(),
            'actionsAlignment' => $this->getHeaderActionsAlignment(),
            'breadcrumbs' => filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : [],
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            'rm' => $this->categoryTab('RM', ['raw-material'], ['raw material', 'rm']),
            'srm' => $this->categoryTab('SRM', ['srm', 'raw-clean', 'premix'], ['srm', 'raw clean', 'premix']),
            'fg' => $this->categoryTab('FG', ['finished-goods'], ['finished goods', 'fg']),
        ];
    }

    /**
     * @param  array<int, string>  $slugs
     * @param  array<int, string>  $names
     */
    protected function categoryTab(string $label, array $slugs, array $names): Tab
    {
        return Tab::make($label)
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->whereHas('category', function (Builder $query) use ($slugs, $names): void {
                        $query->whereIn('slug', $slugs)
                            ->orWhereIn('slug', array_map(fn (string $slug): string => str_replace(' ', '-', $slug), $names))
                            ->orWhereIn('category_type', $slugs)
                            ->orWhereRaw('LOWER(name) IN ('.implode(',', array_fill(0, count($names), '?')).')', $names);
                    })
                    ->withSum('stockBalances as stock_qty', 'qty_on_hand'),
            );
    }
}
