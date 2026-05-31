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
            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                <table class="min-w-[980px] w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <tr>
                            <th class="w-12 px-3 py-2 text-left">No</th>
                            <th class="w-44 px-3 py-2 text-left">Kode Item</th>
                            <th class="px-3 py-2 text-left">Keterangan</th>
                            <th class="w-36 px-3 py-2 text-left">Kategori</th>
                            <th class="w-32 px-3 py-2 text-right">Jumlah</th>
                            <th class="w-28 px-3 py-2 text-left">Satuan</th>
                            <th class="w-36 px-3 py-2 text-right">Harga</th>
                            <th class="w-40 px-3 py-2 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2">1</td>
                            <td class="px-3 py-2">
                                <select wire:model.live="itemId" class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500">
                                    <option value="">Pilih Item</option>
                                    @foreach($this->itemOptions() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">{{ $selectedItem?->name ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <select wire:model="itemKind" class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500">
                                    <option value="">Pilih Kategori</option>
                                    @foreach($this->itemKindOptions() as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="any" min="0" wire:model.live="orderedQty" class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500" />
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
                                <input type="number" step="any" min="0" wire:model.live="unitCost" class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500" />
                            </td>
                            <td class="px-3 py-2 text-right font-semibold">{{ \App\Support\IndoNumber::rupiah($this->lineTotal()) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <label class="mt-4 block text-sm font-medium text-gray-700 dark:text-gray-200">Catatan
                <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-red-500"></textarea>
            </label>

            <button type="button" wire:click="createPurchaseOrder" class="mt-4 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                Buat Draft PO
            </button>
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
