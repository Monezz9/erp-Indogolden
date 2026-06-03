<x-filament-panels::page>
    <div class="space-y-6">
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 p-4 font-bold dark:border-gray-800">PO Siap Diterima</div>
            <table class="min-w-[1000px] w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left">PO</th>
                        <th class="px-3 py-2 text-left">Supplier</th>
                        <th class="px-3 py-2 text-left">Gudang</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Dipesan</th>
                        <th class="px-3 py-2 text-right">Diterima</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->receivablePurchaseOrders() as $po)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2 font-medium">{{ $po->po_number }}</td>
                            <td class="px-3 py-2">{{ $po->supplier?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $po->warehouse?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ \App\Enums\PurchaseOrderStatus::options()[$po->status->value] ?? $po->status->value }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($po->items->sum('ordered_qty')) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($po->items->sum('received_qty')) }}</td>
                            <td class="px-3 py-2 text-right">
                                <button type="button" wire:click="createReceipt({{ $po->id }})" class="rounded-lg bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700">Buat Penerimaan</button>
                            </td>
                        </tr>
                        <tr class="border-t border-gray-100 bg-gray-50/60 dark:border-gray-800 dark:bg-gray-950/40">
                            <td colspan="7" class="px-3 py-3">
                                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                                    <table class="min-w-[980px] w-full text-xs">
                                        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            <tr>
                                                <th class="px-3 py-2 text-left">SKU</th>
                                                <th class="px-3 py-2 text-left">Barang Dipesan</th>
                                                <th class="px-3 py-2 text-right">Qty Beli</th>
                                                <th class="px-3 py-2 text-left">Sat. Beli</th>
                                                <th class="px-3 py-2 text-right">Qty Stok</th>
                                                <th class="px-3 py-2 text-left">Sat. Stok</th>
                                                <th class="px-3 py-2 text-right">Sudah Diterima</th>
                                                <th class="px-3 py-2 text-right">Sisa Terima</th>
                                                <th class="px-3 py-2 text-right">Stok Digital</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($po->items as $line)
                                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                                    <td class="px-3 py-2 font-medium">{{ $line->item?->sku ?? '-' }}</td>
                                                    <td class="px-3 py-2">{{ $line->item?->name ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line->purchase_qty ?? $line->ordered_qty) }}</td>
                                                    <td class="px-3 py-2">{{ $line->purchaseUnit?->code ?? $line->unit?->code ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line->ordered_qty) }}</td>
                                                    <td class="px-3 py-2">{{ $line->unit?->code ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line->received_qty) }}</td>
                                                    <td class="px-3 py-2 text-right font-semibold">{{ \App\Support\IndoNumber::decimal($line->remainingQty()) }}</td>
                                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($this->stockOnHandForLine($line, $po->warehouse_id)) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-gray-500">Tidak ada PO yang siap diterima.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <label class="text-sm">Status GR
                <select wire:model.live="receiptStatus" class="mt-1 block w-full max-w-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    @foreach($this->receiptStatusOptions() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-[1000px] w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left">GR</th>
                        <th class="px-3 py-2 text-left">PO</th>
                        <th class="px-3 py-2 text-left">Gudang</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->receipts() as $receipt)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2 font-medium">{{ $receipt->receipt_number }}</td>
                            <td class="px-3 py-2">{{ $receipt->purchaseOrder?->po_number ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $receipt->warehouse?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $receipt->receipt_date?->format('d M Y') }}</td>
                            <td class="px-3 py-2">{{ \App\Enums\GoodsReceiptStatus::options()[$receipt->status->value] ?? $receipt->status->value }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($receipt->items->sum('received_qty')) }}</td>
                            <td class="px-3 py-2 text-right">
                                @if($receipt->status === \App\Enums\GoodsReceiptStatus::Draft)
                                    <button type="button" wire:click="confirm({{ $receipt->id }})" class="rounded-lg bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700">Konfirmasi Terima</button>
                                @else
                                    <span class="text-xs text-gray-500">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="border-t border-gray-100 bg-gray-50/60 dark:border-gray-800 dark:bg-gray-950/40">
                            <td colspan="7" class="px-3 py-3">
                                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                                    <table class="min-w-[900px] w-full text-xs">
                                        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            <tr>
                                                <th class="px-3 py-2 text-left">SKU</th>
                                                <th class="px-3 py-2 text-left">Barang Diterima</th>
                                                <th class="px-3 py-2 text-right">Qty Beli</th>
                                                <th class="px-3 py-2 text-left">Sat. Beli</th>
                                                <th class="px-3 py-2 text-right">Qty Masuk Stok</th>
                                                <th class="px-3 py-2 text-left">Sat. Stok</th>
                                                <th class="px-3 py-2 text-right">Stok Digital</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($receipt->items as $line)
                                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                                    <td class="px-3 py-2 font-medium">{{ $line->item?->sku ?? '-' }}</td>
                                                    <td class="px-3 py-2">{{ $line->item?->name ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($line->purchase_qty ?? $line->received_qty) }}</td>
                                                    <td class="px-3 py-2">{{ $line->purchaseUnit?->code ?? $line->unit?->code ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-right font-semibold">{{ \App\Support\IndoNumber::decimal($line->received_qty) }}</td>
                                                    <td class="px-3 py-2">{{ $line->unit?->code ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($this->stockOnHandForLine($line, $receipt->warehouse_id)) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-gray-500">Belum ada penerimaan barang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
