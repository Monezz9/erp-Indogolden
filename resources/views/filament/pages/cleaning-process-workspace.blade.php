<x-filament-panels::page>
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-5">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-bold text-gray-950 dark:text-white">Posting Pembersihan</h2>

                @php
                    $selectedItem = $this->selectedItem();
                @endphp

                <div
                    x-data="{
                        inputQty: @entangle('inputQty').defer,
                        outputQty: @entangle('outputQty').defer,
                        decimal(value) {
                            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(value || 0));
                        },
                        shrinkageQty() {
                            return Math.max(Number(this.inputQty || 0) - Number(this.outputQty || 0), 0);
                        },
                        shrinkagePercent() {
                            const input = Number(this.inputQty || 0);

                            return input > 0 ? (this.shrinkageQty() / input) * 100 : 0;
                        },
                    }"
                >
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal
                        <input type="date" wire:model="processDate" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </label>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Barang RM
                        <select wire:model.live="itemId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Pilih Barang Raw Dirty</option>
                            @foreach($this->itemOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800">
                        <div class="text-gray-500">Stok Raw Dirty</div>
                        <div class="mt-1 text-lg font-bold text-gray-950 dark:text-white">
                            {{ \App\Support\IndoNumber::decimal($this->availableQty()) }} {{ $selectedItem?->defaultUnit?->code }}
                        </div>
                    </div>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Qty Masuk
                        <input type="number" step="any" min="0" x-model.number="inputQty" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </label>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Qty Hasil Bersih
                        <input type="number" step="any" min="0" x-model.number="outputQty" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </label>

                    <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800">
                        <div class="text-gray-500">Susut</div>
                        <div class="mt-1 text-lg font-bold text-gray-950 dark:text-white">
                            <span x-text="decimal(shrinkageQty())">{{ \App\Support\IndoNumber::decimal($this->shrinkageQty()) }}</span> {{ $selectedItem?->defaultUnit?->code }}
                        </div>
                        <div class="text-xs text-gray-500"><span x-text="decimal(shrinkagePercent())">{{ number_format($this->shrinkagePercent(), 2, ',', '.') }}</span>%</div>
                    </div>
                </div>

                <label class="mt-4 block text-sm font-medium text-gray-700 dark:text-gray-200">Catatan
                    <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                </label>

                <button
                    type="button"
                    x-on:click="(async () => {
                        await $wire.set('inputQty', Number(inputQty || 0));
                        await $wire.set('outputQty', Number(outputQty || 0));
                        await $wire.post();
                    })()"
                    class="mt-4 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                >
                    Posting Pembersihan
                </button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <table class="min-w-[960px] w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <tr>
                            <th class="px-3 py-2 text-left">No Proses</th>
                            <th class="px-3 py-2 text-left">Tanggal</th>
                            <th class="px-3 py-2 text-left">Gudang</th>
                            <th class="px-3 py-2 text-left">Barang RM</th>
                            <th class="px-3 py-2 text-left">Hasil Raw Clean</th>
                            <th class="px-3 py-2 text-right">Masuk</th>
                            <th class="px-3 py-2 text-right">Hasil</th>
                            <th class="px-3 py-2 text-right">Susut</th>
                            <th class="px-3 py-2 text-right">HPP Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->recentProcesses() as $process)
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-2 font-medium">{{ $process->process_number }}</td>
                                <td class="px-3 py-2">{{ $process->process_date?->format('d M Y') }}</td>
                                <td class="px-3 py-2">{{ $process->warehouse?->name ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $process->item?->name ?? '-' }}</td>
                                <td class="px-3 py-2">{{ $process->outputItem?->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($process->input_qty) }} {{ $process->unit?->code }}</td>
                                <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($process->output_qty) }} {{ $process->unit?->code }}</td>
                                <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($process->shrinkage_qty) }} {{ $process->unit?->code }}</td>
                                <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($process->output_unit_cost) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-3 py-8 text-center text-gray-500">Belum ada proses pembersihan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-base font-bold text-gray-950 dark:text-white">Alur Stok</h2>
            <div class="mt-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Keluar</span>
                    <span class="font-semibold">Stok Mentah Kotor</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Masuk</span>
                    <span class="font-semibold">Item RC / Stok Mentah Bersih</span>
                </div>
                <div class="border-t border-gray-100 pt-3 dark:border-gray-800">
                    Nilai susut tidak menjadi stok. Nilainya ikut dibebankan ke HPP hasil bersih.
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
