<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:flex-row md:items-end md:justify-between dark:border-gray-800 dark:bg-gray-900">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Status Pengadaan
                <select wire:model.live="status" class="mt-1 block w-full max-w-xs rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @foreach($this->statusOptions() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <a href="{{ \App\Filament\Pages\ProcurementRequestWorkspace::getUrl() }}" class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                TAMBAH
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-[1280px] w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left">Pengadaan</th>
                        <th class="px-3 py-2 text-left">Supplier</th>
                        <th class="px-3 py-2 text-left">Gudang</th>
                        <th class="px-3 py-2 text-left">Invoice</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Item</th>
                        <th class="px-3 py-2 text-right">Masuk Stok</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->rows() as $row)
                        @php
                            $receivedQty = $row->items->sum('received_qty');
                        @endphp
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2 font-medium">{{ $row->receipt_number }}</td>
                            <td class="px-3 py-2">{{ $row->supplier?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $row->warehouse?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $row->invoice_number ?: '-' }}</td>
                            <td class="px-3 py-2">{{ $row->receipt_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-3 py-2">{{ \App\Enums\GoodsReceiptStatus::options()[$row->status->value] ?? $row->status->value }}</td>
                            <td class="px-3 py-2 text-right">{{ $row->items->count() }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($receivedQty) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($row->grand_total) }}</td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" wire:click="toggleReview({{ $row->id }})" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                                    {{ $reviewPurchaseOrderId === $row->id ? 'Tutup' : 'Review' }}
                                </button>
                            </td>
                        </tr>
                        @if($reviewPurchaseOrderId === $row->id)
                            <tr class="border-t border-gray-100 bg-gray-50/60 dark:border-gray-800 dark:bg-gray-950/40">
                                <td colspan="10" class="px-3 py-3">
                                    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                                        <table class="min-w-[1000px] w-full text-xs">
                                            <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">SKU</th>
                                                    <th class="px-3 py-2 text-left">Barang</th>
                                                    <th class="px-3 py-2 text-left">Kategori</th>
                                                    <th class="px-3 py-2 text-right">Qty Beli</th>
                                                    <th class="px-3 py-2 text-left">Sat. Beli</th>
                                                    <th class="px-3 py-2 text-right">Qty Stok</th>
                                                    <th class="px-3 py-2 text-left">Sat. Stok</th>
                                                    <th class="px-3 py-2 text-right">Harga/Stok</th>
                                                    <th class="px-3 py-2 text-right">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($row->items as $line)
                                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                                        <td class="px-3 py-2 font-medium">{{ $line->item?->sku ?? '-' }}</td>
                                                        <td class="px-3 py-2">{{ $line->item?->name ?? '-' }}</td>
                                                        <td class="px-3 py-2">{{ $line->item?->category?->name ?? '-' }}</td>
                                                        <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line->purchase_qty ?? $line->ordered_qty) }}</td>
                                                        <td class="px-3 py-2">{{ $line->purchaseUnit?->code ?? $line->unit?->code ?? '-' }}</td>
                                                        <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line->received_qty) }}</td>
                                                        <td class="px-3 py-2">{{ $line->unit?->code ?? '-' }}</td>
                                                        <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($line->unit_cost) }}</td>
                                                        <td class="px-3 py-2 text-right font-semibold">{{ \App\Support\IndoNumber::rupiah((float) $line->received_qty * (float) $line->unit_cost) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="10" class="px-3 py-8 text-center text-gray-500">Belum ada histori pengadaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
