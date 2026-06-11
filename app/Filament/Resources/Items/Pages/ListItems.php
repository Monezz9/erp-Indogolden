<?php

namespace App\Filament\Resources\Items\Pages;

use App\Enums\ItemStageCode;
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
            'fg' => $this->stageTab('FG', ItemStageCode::FinishedGoods),
            'rm' => $this->stageTab('RM', ItemStageCode::RawDirty),
            'rc' => $this->stageTab('RC', ItemStageCode::RawClean),
            'srm' => $this->stageTab('SRM', ItemStageCode::Srm),
            'premix' => Tab::make('Premix')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('item_type', 'premix'),
                ),
        ];
    }

    protected function stageTab(string $label, ItemStageCode $stage): Tab
    {
        return Tab::make($label)
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->where('item_type', '!=', 'premix')
                    ->where(function (Builder $query) use ($stage): void {
                        $query
                            ->whereHas(
                                'defaultStage',
                                fn (Builder $stageQuery): Builder => $stageQuery->where('code', $stage->value),
                            )
                            ->orWhereHas(
                                'stockBalances',
                                fn (Builder $balanceQuery): Builder => $balanceQuery
                                    ->where('qty_on_hand', '>', 0)
                                    ->whereHas('stage', fn (Builder $stageQuery): Builder => $stageQuery->where('code', $stage->value)),
                            );
                    })
                    ->withSum([
                        'stockBalances as stock_qty' => fn (Builder $balanceQuery): Builder => $balanceQuery
                            ->whereHas('stage', fn (Builder $stageQuery): Builder => $stageQuery->where('code', $stage->value)),
                    ], 'qty_on_hand'),
            );
    }
}
