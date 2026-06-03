<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-2 dark:border-gray-800 dark:bg-gray-900">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Status PO
                <select wire:model.live="status" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    @foreach($this->statusOptions() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Catatan Finance
                <input type="text" wire:model="financeNotes" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </label>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-[1120px] w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left">PO</th>
                        <th class="px-3 py-2 text-left">Supplier</th>
                        <th class="px-3 py-2 text-left">Gudang</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Item</th>
                        <th class="px-3 py-2 text-right">Total Akhir</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->rows() as $row)
                        @php
                            $isReviewing = $reviewPurchaseOrderId === $row->id;
                        @endphp
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2 font-medium">{{ $row->po_number }}</td>
                            <td class="px-3 py-2">{{ $row->supplier?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $row->warehouse?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $row->order_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-3 py-2">{{ \App\Enums\PurchaseOrderStatus::options()[$row->status->value] ?? $row->status->value }}</td>
                            <td class="px-3 py-2 text-right">{{ $row->items->count() }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($row->grand_total) }}</td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" wire:click="toggleReview({{ $row->id }})" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                                    {{ $isReviewing ? 'Tutup' : 'Review' }}
                                </button>
                                @if($row->status === \App\Enums\PurchaseOrderStatus::Submitted)
                                    <button type="button" wire:click="approve({{ $row->id }})" class="rounded-lg bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700">Setujui</button>
                                    <button type="button" wire:click="reject({{ $row->id }})" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Tolak</button>
                                @endif
                            </td>
                        </tr>
                        @if($isReviewing)
                            <tr class="border-t border-gray-100 bg-gray-50/70 dark:border-gray-800 dark:bg-gray-950/40">
                                <td colspan="8" class="px-3 py-3">
                                    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                                        <table class="min-w-[980px] w-full text-xs">
                                            <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                <tr>
                                                    <th class="px-3 py-2 text-left">Barang</th>
                                                    <th class="px-3 py-2 text-right">Qty Beli</th>
                                                    <th class="px-3 py-2 text-left">Satuan Beli</th>
                                                    <th class="px-3 py-2 text-right">Isi/Satuan</th>
                                                    <th class="px-3 py-2 text-right">Qty Stok</th>
                                                    <th class="px-3 py-2 text-left">Satuan Stok</th>
                                                    <th class="px-3 py-2 text-right">Harga/Beli</th>
                                                    <th class="px-3 py-2 text-right">HPP/Stok</th>
                                                    <th class="px-3 py-2 text-right">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($row->items as $line)
                                                    <tr class="border-t border-gray-100 dark:border-gray-800">
                                                        <td class="px-3 py-2">
                                                            <div class="font-semibold text-gray-900 dark:text-white">{{ $line->item?->name ?? '-' }}</div>
                                                            <div class="text-gray-500">{{ $line->item?->sku ?: '-' }}</div>
                                                        </td>
                                                        <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line->purchase_qty ?? $line->ordered_qty) }}</td>
                                                        <td class="px-3 py-2">{{ $line->purchaseUnit?->code ?? $line->unit?->code ?? '-' }}</td>
                                                        <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line->conversion_qty ?: 1) }}</td>
                                                        <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line->ordered_qty) }}</td>
                                                        <td class="px-3 py-2">{{ $line->unit?->code ?? '-' }}</td>
                                                        <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($line->purchase_unit_cost ?? $line->unit_cost) }}</td>
                                                        <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($line->unit_cost) }}</td>
                                                        <td class="px-3 py-2 text-right font-semibold">{{ \App\Support\IndoNumber::rupiah($line->line_total) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="8" class="px-3 py-8 text-center text-gray-500">Tidak ada PO untuk review.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
