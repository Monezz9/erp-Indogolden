<?php

namespace App\Filament\Resources\Items\Pages;

use App\Enums\ItemStageCode;
use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\Items\ItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListItems extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'rm' => $this->stageTab('RM', ItemStageCode::RawDirty),
            'raw_clean' => $this->stageTab('Raw Clean', ItemStageCode::RawClean),
            'premix' => Tab::make('Premix')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('item_type', 'premix'),
                ),
            'srm' => $this->stageTab('SRM', ItemStageCode::Srm),
            'fg' => $this->stageTab('FG', ItemStageCode::FinishedGoods),
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
