<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gradient-to-r from-red-700 via-red-600 to-rose-500 p-5 text-white shadow-sm">
            <h2 class="text-2xl font-black">Pengiriman Besok</h2>
            <p class="mt-1 text-sm text-red-100">Checklist kebutuhan kirim berdasarkan request cabang untuk tanggal pengiriman.</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pengiriman</label>
            <input type="date" wire:model.live="deliveryDate" class="mt-1 block w-full max-w-xs rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="font-bold text-gray-900 dark:text-white">Tampilan Per Cabang</h3>
                <div class="mt-3 space-y-4">
                    @forelse($this->groupedByBranch() as $branchName => $requests)
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="font-semibold text-red-700 dark:text-red-300">{{ $branchName }}</div>
                            <div class="mt-2 space-y-2 text-sm">
                                @foreach($requests as $req)
                                    <div class="rounded border border-gray-100 p-2 dark:border-gray-800">
                                        <div class="text-xs text-gray-500">{{ $req->request_number }} • {{ str($req->status)->replace('_', ' ')->title() }}</div>
                                        <ul class="mt-1">
                                            @foreach($req->items as $item)
                                                <li>• {{ $item->product?->name }} - Req {{ \App\Support\IndoNumber::decimal($item->requested_qty) }} {{ $item->unit?->name }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">Tidak ada request untuk tanggal ini.</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="font-bold text-gray-900 dark:text-white">Picking List Gabungan</h3>
                <div class="mt-3 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-3 py-2 text-left">SKU</th>
                                <th class="px-3 py-2 text-left">Barang</th>
                                <th class="px-3 py-2 text-right">Diminta</th>
                                <th class="px-3 py-2 text-right">Disetujui</th>
                                <th class="px-3 py-2 text-right">Dipacking</th>
                                <th class="px-3 py-2 text-right">Dikirim</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->consolidated() as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2">{{ $row['sku'] }}</td>
                                    <td class="px-3 py-2">{{ $row['item'] }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row['qty_request']) }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row['qty_approved']) }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row['qty_packed']) }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row['qty_shipped']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-3 py-8 text-center text-gray-500">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
