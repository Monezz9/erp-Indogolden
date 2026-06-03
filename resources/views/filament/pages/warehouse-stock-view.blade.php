<x-filament-panels::page>
    <div class="space-y-5">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-black text-gray-950 dark:text-white">{{ $this->warehouseName() }}</h2>
                    <div class="mt-1 text-sm text-gray-500">Tampilan stok berdasarkan saldo gudang saat ini.</div>
                </div>
                @if(count($this->stageOptions()) > 1)
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Tahap Stok
                        <select wire:model.live="stageFilter" class="mt-1 block w-full min-w-56 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @foreach($this->stageOptions() as $key => $label)
                                <option value="{{ $key }}">{{ \App\Support\InventoryLabels::stage($key, $label) }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                    <div class="text-xs text-gray-500">Jumlah Baris Stok</div>
                    <div class="mt-1 text-xl font-black text-gray-950 dark:text-white">{{ $this->rows()->count() }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                    <div class="text-xs text-gray-500">Total Qty</div>
                    <div class="mt-1 text-xl font-black text-gray-950 dark:text-white">{{ \App\Support\IndoNumber::decimal($this->totalQty()) }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                    <div class="text-xs text-gray-500">Nilai Stok</div>
                    <div class="mt-1 text-xl font-black text-red-600">{{ \App\Support\IndoNumber::rupiah($this->totalValue()) }}</div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-[980px] w-full text-sm">
                <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    <tr>
                        <th class="px-3 py-2 text-left">Barang</th>
                        <th class="px-3 py-2 text-left">Tahap</th>
                        <th class="px-3 py-2 text-left">Gudang</th>
                        <th class="px-3 py-2 text-left">Cabang</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-left">Satuan</th>
                        <th class="px-3 py-2 text-right">HPP Rata-rata</th>
                        <th class="px-3 py-2 text-right">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->rows() as $row)
                        <tr class="border-t border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2">
                                <div class="font-semibold text-gray-950 dark:text-white">{{ $row->item?->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500">{{ $row->item?->sku ?: '-' }}</div>
                            </td>
                            <td class="px-3 py-2">{{ \App\Support\InventoryLabels::stage($row->stage?->code, $row->stage?->name) }}</td>
                            <td class="px-3 py-2">{{ $row->warehouse?->name ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $row->branch?->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ \App\Support\IndoNumber::decimal($row->qty_on_hand) }}</td>
                            <td class="px-3 py-2">{{ $row->item?->defaultUnit?->code ?? '-' }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($row->avg_cost) }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ \App\Support\IndoNumber::rupiah($row->total_value) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-8 text-center text-gray-500">Belum ada stok untuk gudang ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
