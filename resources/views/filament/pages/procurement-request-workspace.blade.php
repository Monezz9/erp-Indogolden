<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-gray-950 dark:text-white">Buat Pengadaan Barang</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">No Transaksi
                    <input type="text" wire:model="transactionNumber" readonly class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 font-semibold text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                </label>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal
                    <input type="date" wire:model="orderDate" readonly class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 font-semibold text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                </label>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Supplier
                    <select wire:model="supplierId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500">
                        <option value="">Pilih Supplier</option>
                        @foreach($this->supplierOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            @php($selectedItem = $this->selectedItem())
            <div
                class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800"
                style="padding-bottom: 18px;"
                x-data="{
                    purchaseQty: @entangle('purchaseQty'),
                    conversionQty: @entangle('conversionQty'),
                    unitCost: @entangle('unitCost'),
                    baseQty() {
                        return Number(this.purchaseQty || 0) * Number(this.conversionQty || 0);
                    },
                    baseUnitCost() {
                        const qty = this.baseQty();
                        return qty > 0 ? Number(this.unitCost || 0) / qty : 0;
                    },
                    decimal(value) {
                        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(value || 0));
                    },
                    rupiah(value) {
                        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(Number(value || 0));
                    },
                }"
            >
                <table class="w-full table-fixed text-sm" style="min-width: 1370px; margin-bottom: 10px;">
                    <colgroup>
                        <col style="width: 44px;">
                        <col style="width: 240px;">
                        <col style="width: 150px;">
                        <col style="width: 145px;">
                        <col style="width: 90px;">
                        <col style="width: 170px;">
                        <col style="width: 105px;">
                        <col style="width: 105px;">
                        <col style="width: 120px;">
                        <col style="width: 120px;">
                        <col style="width: 120px;">
                        <col style="width: 85px;">
                    </colgroup>
                    <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <tr>
                            <th class="px-3 py-2 text-left">No</th>
                            <th class="px-3 py-2 text-left">Kode Item</th>
                            <th class="px-3 py-2 text-left">Keterangan</th>
                            <th class="px-3 py-2 text-left">Kategori</th>
                            <th class="px-3 py-2 text-right">Qty Beli</th>
                            <th class="px-3 py-2 text-left">Satuan Beli</th>
                            <th class="px-3 py-2 text-right">Isi/Satuan</th>
                            <th class="px-3 py-2 text-right">Qty Stok</th>
                            <th class="px-3 py-2 text-left">Satuan Stok</th>
                            <th class="px-3 py-2 text-right">Total Harga Beli</th>
                            <th class="px-3 py-2 text-right">Total</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2">1</td>
                            <td class="relative px-3 py-2">
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="itemSearch"
                                    wire:keydown.enter.prevent="openItemSearchResults"
                                    placeholder="Ketik kode / nama"
                                    autocomplete="off"
                                    style="min-width: 210px;"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500"
                                />
                                @if($showItemSearchResults && trim((string) $itemSearch) !== '' && ! $itemId)
                                    <div class="absolute left-3 right-3 top-[46px] z-50 max-h-72 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900">
                                        @forelse($this->itemSearchResults() as $result)
                                            <button type="button" wire:click="selectItem({{ $result['id'] }})" class="block w-full px-3 py-2 text-left hover:bg-red-50 dark:hover:bg-gray-800">
                                                <span class="block font-semibold text-gray-900 dark:text-white">{{ $result['label'] }}</span>
                                                <span class="block text-xs text-gray-500">{{ $result['category'] }}</span>
                                            </button>
                                        @empty
                                            <div class="px-3 py-2 text-sm text-gray-500">Barang tidak ditemukan.</div>
                                        @endforelse
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $selectedItem?->name ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <input type="text" value="{{ $this->selectedItemCategoryLabel() }}" readonly class="block w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="any" min="0" x-model.number="purchaseQty" class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500" />
                            </td>
                            <td class="px-3 py-2">
                                <select wire:model="purchaseUnitId" class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500">
                                    <option value="">Pilih Satuan</option>
                                    @foreach($this->unitOptions() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="any" min="0" x-model.number="conversionQty" class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500" />
                            </td>
                            <td class="px-3 py-2 text-right font-semibold">
                                <span x-text="decimal(baseQty())">{{ \App\Support\IndoNumber::decimal($this->baseQty()) }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <select wire:model="unitId" class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500">
                                    <option value="">Pilih Satuan</option>
                                    @foreach($this->unitOptions() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="any" min="0" x-model.number="unitCost" class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500" />
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="font-semibold" x-text="rupiah(unitCost)">{{ \App\Support\IndoNumber::rupiah($this->lineTotal()) }}</div>
                                <div class="text-xs text-gray-500">
                                    <span x-text="rupiah(baseUnitCost())">{{ \App\Support\IndoNumber::rupiah($this->baseUnitCost()) }}</span>/{{ $this->selectedStockUnitCode() }}
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" wire:click="addItemToCart" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
                                    Tambah
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="min-w-[1180px] w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <tr>
                            <th class="w-12 px-3 py-2 text-left">No</th>
                            <th class="px-3 py-2 text-left">Barang</th>
                            <th class="w-32 px-3 py-2 text-left">Kategori</th>
                            <th class="w-28 px-3 py-2 text-right">Qty Beli</th>
                            <th class="w-32 px-3 py-2 text-left">Satuan Beli</th>
                            <th class="w-28 px-3 py-2 text-right">Isi/Satuan</th>
                            <th class="w-28 px-3 py-2 text-right">Qty Stok</th>
                            <th class="w-32 px-3 py-2 text-left">Satuan Stok</th>
                            <th class="w-36 px-3 py-2 text-right">Total</th>
                            <th class="w-28 px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cart as $index => $line)
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-2">{{ $index + 1 }}</td>
                                <td class="px-3 py-2">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $line['item_name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $line['item_label'] }}</div>
                                </td>
                                <td class="px-3 py-2">{{ $line['item_kind'] ?: '-' }}</td>
                                <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line['purchase_qty']) }}</td>
                                <td class="px-3 py-2">{{ $line['purchase_unit_label'] }}</td>
                                <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line['conversion_qty']) }}</td>
                                <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line['ordered_qty']) }}</td>
                                <td class="px-3 py-2">{{ $line['unit_label'] }}</td>
                                <td class="px-3 py-2 text-right">
                                    <div class="font-semibold">{{ \App\Support\IndoNumber::rupiah($line['line_total']) }}</div>
                                    <div class="text-xs text-gray-500">{{ \App\Support\IndoNumber::rupiah($line['unit_cost']) }}/{{ \Illuminate\Support\Str::before($line['unit_label'], ' - ') }}</div>
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" wire:click="removeCartItem({{ $index }})" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-3 py-8 text-center text-gray-500">Belum ada barang di draft PO.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($cart !== [])
                        <tfoot class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                            <tr>
                                <td colspan="8" class="px-3 py-2 text-right font-bold">Total Draft PO</td>
                                <td class="px-3 py-2 text-right font-bold">{{ \App\Support\IndoNumber::rupiah($this->cartTotal()) }}</td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" wire:click="clearCart" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900">
                                        Kosongkan
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <label class="mt-4 block text-sm font-medium text-gray-700 dark:text-gray-200">Catatan
                <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500"></textarea>
            </label>

            <div class="mt-4 flex items-center justify-between gap-3">
                <div class="text-sm text-gray-500">
                    {{ count($cart) }} barang siap dibuat menjadi 1 draft PO untuk supplier terpilih.
                </div>
                <button type="button" wire:click="createPurchaseOrder" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Buat Draft PO
                </button>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Status
                <select wire:model.live="status" class="mt-1 block w-full max-w-xs rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500">
                    @foreach($this->statusOptions() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-[1100px] w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left">PO</th>
                        <th class="px-3 py-2 text-left">Supplier</th>
                        <th class="px-3 py-2 text-left">Gudang</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Item</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->rows() as $row)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2 font-medium">{{ $row->po_number }}</td>
                            <td class="px-3 py-2">{{ $row->supplier?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $row->warehouse?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $row->order_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-3 py-2">{{ \App\Enums\PurchaseOrderStatus::options()[$row->status->value] ?? $row->status->value }}</td>
                            <td class="px-3 py-2 text-right">{{ $row->items->count() }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($row->grand_total) }}</td>
                            <td class="px-3 py-2 text-right">
                                @if($row->status === \App\Enums\PurchaseOrderStatus::Draft)
                                    <button type="button" wire:click="submit({{ $row->id }})" class="rounded-lg bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700">Ajukan</button>
                                    <button type="button" wire:click="cancel({{ $row->id }})" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Batalkan</button>
                                @else
                                    <span class="text-xs text-gray-500">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-3 py-8 text-center text-gray-500">Belum ada pengadaan barang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
