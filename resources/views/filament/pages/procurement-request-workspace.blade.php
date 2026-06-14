<x-filament-panels::page>
    @php
        $canSave = $this->canSaveDraft();
    @endphp

    <div
        class="space-y-4"
        x-data="{
            purchaseQty: @entangle('purchaseQty'),
            unitCost: @entangle('unitCost'),
            rupiah(value) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));
            },
        }"
        x-on:focus-procurement-item-search.window="$nextTick(() => $refs.itemSearch?.focus())"
    >
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-3 md:grid-cols-3">
                <label class="ig-procurement-field">
                    <span>Supplier</span>
                    <select wire:model.live="supplierId">
                        <option value="">Pilih supplier</option>
                        @foreach($this->supplierOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="ig-procurement-field">
                    <span>Tanggal</span>
                    <input type="date" wire:model.live="orderDate" />
                </label>

                <label class="ig-procurement-field">
                    <span>No Invoice</span>
                    <input type="text" wire:model.live="invoiceNumber" placeholder="Opsional" />
                </label>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-3 lg:grid-cols-[minmax(260px,1.8fr)_120px_170px_160px_auto] lg:items-end">
                <label class="ig-procurement-field relative">
                    <span>Barang</span>
                    <input
                        x-ref="itemSearch"
                        type="text"
                        wire:model.live.debounce.300ms="itemSearch"
                        wire:focus="openItemSearchResults"
                        placeholder="Cari nama atau SKU"
                        autocomplete="off"
                    />

                    @if(trim((string) $itemSearch) !== '' && ! $itemId)
                        <div class="ig-procurement-search-results">
                            @forelse($this->itemSearchResults() as $result)
                                <button type="button" wire:click="selectItem({{ $result['id'] }})">
                                    <strong>{{ $result['label'] }}</strong>
                                    <span>
                                        {{ $result['category'] }}
                                        &bull; Stok: {{ \App\Support\IndoNumber::decimal($result['stock_qty']) }} {{ $result['stock_unit'] }}
                                        &bull; HPP:
                                        @if($result['hpp'] > 0)
                                            {{ \App\Support\IndoNumber::rupiah($result['hpp']) }}/{{ $result['stock_unit'] }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </button>
                            @empty
                                <div class="ig-procurement-search-results__empty">Barang tidak ditemukan.</div>
                            @endforelse
                        </div>
                    @endif
                </label>

                <label class="ig-procurement-field">
                    <span>Qty datang</span>
                    <input type="number" step="any" min="0" x-model.number="purchaseQty" />
                </label>

                <label class="ig-procurement-field">
                    <span>Satuan beli</span>
                    <select wire:model.live="purchaseUnitId">
                        <option value="">Pilih satuan</option>
                        @foreach($this->unitOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="ig-procurement-field">
                    <span>Harga beli</span>
                    <input type="number" step="any" min="0" x-model.number="unitCost" />
                </label>

                <button type="button" wire:click="addItemToCart" class="ig-procurement-add-button h-11 justify-center">
                    <x-filament::icon icon="heroicon-o-plus" />
                    Tambahkan
                </button>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Barang</th>
                            <th class="px-4 py-3 text-right font-semibold">Qty beli</th>
                            <th class="px-4 py-3 text-right font-semibold">Qty stok</th>
                            <th class="px-4 py-3 text-right font-semibold">Harga</th>
                            <th class="px-4 py-3 text-right font-semibold">Total</th>
                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cart as $index => $line)
                            @php
                                $isValidProcurementItem = $this->cartLineIsAllowed((int) $line['item_id']);
                                $purchaseUnit = \Illuminate\Support\Str::before($line['purchase_unit_label'], ' - ');
                                $stockUnit = \Illuminate\Support\Str::before($line['unit_label'], ' - ');
                            @endphp
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $line['item_name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $line['item_label'] }}</div>
                                    @unless($isValidProcurementItem)
                                        <span class="mt-1 inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-300">Kategori tidak valid</span>
                                    @endunless
                                </td>
                                <td class="px-4 py-3 text-right">{{ \App\Support\IndoNumber::decimal($line['purchase_qty']) }} {{ $purchaseUnit }}</td>
                                <td class="px-4 py-3 text-right">{{ \App\Support\IndoNumber::decimal($line['ordered_qty']) }} {{ $stockUnit }}</td>
                                <td class="px-4 py-3 text-right">{{ \App\Support\IndoNumber::rupiah($line['purchase_unit_cost']) }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ \App\Support\IndoNumber::rupiah($line['line_total']) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="removeCartItem({{ $index }})" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    Belum ada barang. Cari barang, isi qty dan harga, lalu tambahkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 bg-gray-50 px-4 py-4 dark:border-gray-800 dark:bg-gray-950/40">
                <div class="flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:justify-end">
                    <div class="rounded-md bg-white px-4 py-2 dark:bg-gray-900">
                        <span class="text-gray-500 dark:text-gray-400">Total item</span>
                        <strong class="ml-2 text-gray-950 dark:text-white">{{ count($cart) }}</strong>
                    </div>
                    <div class="rounded-md bg-white px-4 py-2 dark:bg-gray-900">
                        <span class="text-gray-500 dark:text-gray-400">Total nilai pengadaan</span>
                        <strong class="ml-2 text-gray-950 dark:text-white">{{ \App\Support\IndoNumber::rupiah($this->cartTotal()) }}</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <label class="ig-procurement-field">
                <span>Catatan pengadaan <small class="font-normal text-gray-400">(opsional)</small></span>
                <textarea
                    wire:model.live="notes"
                    rows="3"
                    placeholder="Contoh: Pembelian bahan baku mingguan."
                ></textarea>
            </label>

            @unless($canSave)
                <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                    Supplier, tanggal, dan minimal satu barang wajib diisi.
                </div>
            @endunless

            <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ \App\Filament\Pages\ProcurementHistory::getUrl() }}" class="ig-procurement-back-button justify-center">
                    Batal
                </a>

                <button
                    type="button"
                    wire:click="createPurchaseOrder"
                    @disabled(! $canSave)
                    class="ig-procurement-save-button justify-center sm:w-auto"
                >
                    Simpan Pengadaan
                </button>
            </div>
        </section>
    </div>
</x-filament-panels::page>
