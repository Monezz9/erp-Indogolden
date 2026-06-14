<?php

namespace App\Filament\Resources\Items\Tables;

use App\Support\IndoNumber;
use App\Support\InventoryStockStatus;
use App\Models\Warehouse;
use App\Filament\Resources\Items\ItemResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(fn ($livewire): \Illuminate\Contracts\View\View => view('filament.resources.items.tables.item-stage-tabs', [
                'page' => $livewire,
            ]))
            ->searchPlaceholder('Cari nama barang atau SKU...')
            ->persistSearchInSession()
            ->columns([
                ViewColumn::make('name')
                    ->label('Barang')
                    ->view('filament.resources.items.tables.columns.item-name')
                    ->searchable()
                    ->sortable()
                    ->width('320px'),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('danger')
                    ->searchable()
                    ->sortable()
                    ->width('160px'),
                ViewColumn::make('stock_qty')
                    ->label('Stok')
                    ->view('filament.resources.items.tables.columns.item-stock')
                    ->sortable()
                    ->width('230px')
                    ->grow(false),
                TextColumn::make('minimum_stock')
                    ->label('Minimum')
                    ->formatStateUsing(fn (mixed $state, $record): string => IndoNumber::decimal($state).' '.($record->defaultUnit?->code ?? ''))
                    ->alignRight()
                    ->sortable()
                    ->width('130px')
                    ->grow(false),
                TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state))
                    ->alignRight()
                    ->sortable()
                    ->width('145px')
                    ->grow(false),
                TextColumn::make('stock_status')
                    ->label('Status')
                    ->state(fn ($record): string => self::stockStatus($record))
                    ->badge()
                    ->color(fn (string $state): string => InventoryStockStatus::color($state))
                    ->width('110px')
                    ->grow(false),
                TextColumn::make('is_active')
                    ->label('Aktif')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('purchase_price')->label('Harga Beli')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('latest_weighted_avg_cost')->label('Harga Pokok / HPP')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')->label('Keterangan')->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => empty($data['value'])
                        ? $query
                        : $query->whereHas('stockBalances', fn (Builder $balanceQuery): Builder => $balanceQuery->where('warehouse_id', $data['value']))),
                SelectFilter::make('stock_status')
                    ->label('Status Stok')
                    ->options([
                        'safe' => 'Aman',
                        'attention' => 'Perhatian',
                        'critical' => 'Kritis',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => self::applyStockStatusFilter($query, $data['value'] ?? null)),
                SelectFilter::make('item_category_id')
                    ->relationship('category', 'name')
                    ->label('Kategori'),
                SelectFilter::make('default_unit_id')
                    ->relationship('defaultUnit', 'code')
                    ->label('Satuan'),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                Action::make('view_detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->outlined()
                    ->modalWidth(Width::Large)
                    ->modalSubmitAction(false)
                    ->modalContent(fn ($record) => view('filament.resources.items.tables.actions.item-detail', ['record' => $record])),
                Action::make('history')
                    ->label('Histori')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->outlined()
                    ->url(fn ($record): string => ItemResource::getUrl('history', ['record' => $record])),
                EditAction::make()
                    ->label('Edit')
                    ->iconButton()
                    ->outlined()
                    ->color('warning'),
                ReplicateAction::make()
                    ->label('Duplikat')
                    ->iconButton()
                    ->outlined()
                    ->color('gray')
                    ->excludeAttributes(['created_at', 'updated_at'])
                    ->mutateRecordDataUsing(function (array $data): array {
                        $baseSku = (string) ($data['sku'] ?? 'ITEM');
                        $data['sku'] = Str::limit($baseSku.'-COPY-'.Str::upper(Str::random(4)), 60, '');
                        $data['name'] = Str::limit((string) ($data['name'] ?? 'Barang').' (Copy)', 255, '');

                        return $data;
                    }),
                DeleteAction::make()
                    ->label('Hapus')
                    ->iconButton()
                    ->color('danger')
                    ->visible(fn ($record): bool => $record->canBeDeleted()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->contentGrid(null)
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateHeading('Belum ada barang')
            ->emptyStateDescription('Tambahkan master barang pertama untuk mulai mengelola persediaan.')
            ->emptyStateActions([
                Action::make('create_item')
                    ->label('Tambah Barang')
                    ->icon('heroicon-o-plus')
                    ->color('warning')
                    ->url(fn (): string => ItemResource::getUrl('create')),
            ]);
    }

    public static function stockStatus($record): string
    {
        $stock = (float) ($record->stock_qty ?? 0);
        $minimum = (float) ($record->minimum_stock ?? 0);

        return InventoryStockStatus::status($stock, $minimum);
    }

    public static function stockProgress($record): int
    {
        $stock = (float) ($record->stock_qty ?? 0);
        $minimum = (float) ($record->minimum_stock ?? 0);

        return InventoryStockStatus::progress($stock, $minimum);
    }

    protected static function applyStockStatusFilter(Builder $query, ?string $status): Builder
    {
        if (! $status) {
            return $query;
        }

        $stockExpression = '(select coalesce(sum(stock_balances.qty_on_hand), 0) from stock_balances where stock_balances.item_id = items.id)';

        return InventoryStockStatus::applyFilter($query, $status, $stockExpression);
    }
}
