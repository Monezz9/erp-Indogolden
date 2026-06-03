<x-filament-panels::page>
    <div class="space-y-5">
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                wire:click="setProcessType('internal')"
                @class([
                    'rounded-lg px-4 py-2 text-sm font-semibold',
                    'bg-red-600 text-white' => $processType === 'internal',
                    'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200' => $processType !== 'internal',
                ])
            >
                Internal
            </button>
            <button
                type="button"
                wire:click="setProcessType('vendor')"
                @class([
                    'rounded-lg px-4 py-2 text-sm font-semibold',
                    'bg-red-600 text-white' => $processType === 'vendor',
                    'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200' => $processType !== 'vendor',
                ])
            >
                Vendor
            </button>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-5">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">
                        Posting WIP {{ $processType === 'vendor' ? 'Vendor' : 'Internal' }}
                    </h2>

                    @php
                        $selectedInputItem = $this->selectedInputItem();
                        $selectedOutputItem = $this->selectedOutputItem();
                    @endphp

                    <div
                        x-data="{
                            inputQty: @entangle('inputQty').defer,
                            standardConversion: @entangle('standardConversionPerUnit').defer,
                            actualOutputQty: @entangle('actualOutputQty').defer,
                            overheadCost: @entangle('overheadCost').defer,
                            decimal(value) {
                                return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(value || 0));
                            },
                            rupiah(value) {
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
                            },
                            expectedOutput() {
                                return Number(this.inputQty || 0) * Number(this.standardConversion || 0);
                            },
                            variance() {
                                return Number(this.actualOutputQty || 0) - this.expectedOutput();
                            },
                        }"
                    >
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal
                                <input type="date" wire:model="processDate" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            </label>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Barang SRM
                                <select wire:model.live="inputItemId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="">Pilih barang SRM</option>
                                    @foreach($this->inputItemOptions() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Hasil FG
                                <select wire:model.live="outputItemId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                    <option value="">Pilih hasil FG</option>
                                    @foreach($this->outputItemOptions() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-4">
                            <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800">
                                <div class="text-gray-500">Stok SRM</div>
                                <div class="mt-1 text-lg font-bold text-gray-950 dark:text-white">
                                    {{ \App\Support\IndoNumber::decimal($this->availableQty()) }} {{ $selectedInputItem?->defaultUnit?->code }}
                                </div>
                            </div>

                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Qty Proses
                                <input type="number" step="any" min="0" x-model.number="inputQty" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            </label>

                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Konversi / {{ $selectedInputItem?->defaultUnit?->code ?: 'unit' }}
                                <input type="number" step="any" min="0" x-model.number="standardConversion" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            </label>

                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Hasil Aktual
                                <input type="number" step="any" min="0" x-model.number="actualOutputQty" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            </label>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-4">
                            <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800">
                                <div class="text-gray-500">Standar Hasil</div>
                                <div class="mt-1 text-lg font-bold text-gray-950 dark:text-white">
                                    <span x-text="decimal(expectedOutput())">{{ \App\Support\IndoNumber::decimal($this->expectedOutputQty()) }}</span> {{ $selectedOutputItem?->defaultUnit?->code }}
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800">
                                <div class="text-gray-500">Selisih</div>
                                <div class="mt-1 text-lg font-bold text-gray-950 dark:text-white">
                                    <span x-text="decimal(variance())">{{ \App\Support\IndoNumber::decimal($this->varianceQty()) }}</span> {{ $selectedOutputItem?->defaultUnit?->code }}
                                </div>
                            </div>

                            <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Overhead
                                <input type="number" step="any" min="0" x-model.number="overheadCost" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                            </label>

                            <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800">
                                <div class="text-gray-500">Satuan Hasil</div>
                                <div class="mt-1 text-lg font-bold text-gray-950 dark:text-white">
                                    {{ $selectedOutputItem?->defaultUnit?->code ?: '-' }}
                                </div>
                            </div>
                        </div>

                    <label class="mt-4 block text-sm font-medium text-gray-700 dark:text-gray-200">Catatan
                        <textarea wire:model="notes" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                    </label>

                    <button
                        type="button"
                        x-on:click="(async () => {
                            await $wire.set('inputQty', Number(inputQty || 0));
                            await $wire.set('standardConversionPerUnit', Number(standardConversion || 0));
                            await $wire.set('actualOutputQty', Number(actualOutputQty || 0));
                            await $wire.set('overheadCost', Number(overheadCost || 0));
                            await $wire.post();
                        })()"
                        class="mt-4 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                    >
                        Posting WIP
                    </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <table class="min-w-[1080px] w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            <tr>
                                <th class="px-3 py-2 text-left">No Proses</th>
                                <th class="px-3 py-2 text-left">Tanggal</th>
                                <th class="px-3 py-2 text-left">SRM</th>
                                <th class="px-3 py-2 text-left">FG</th>
                                <th class="px-3 py-2 text-right">Qty</th>
                                <th class="px-3 py-2 text-right">Standar</th>
                                <th class="px-3 py-2 text-right">Hasil</th>
                                <th class="px-3 py-2 text-right">Selisih</th>
                                <th class="px-3 py-2 text-right">HPP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->recentProcesses() as $process)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-2 font-medium">{{ $process->process_number }}</td>
                                    <td class="px-3 py-2">{{ $process->process_date?->format('d M Y') }}</td>
                                    <td class="px-3 py-2">{{ $process->inputItem?->name ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $process->outputItem?->name ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($process->input_qty) }} {{ $process->inputUnit?->code }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($process->expected_output_qty) }} {{ $process->outputUnit?->code }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($process->actual_output_qty) }} {{ $process->outputUnit?->code }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($process->variance_qty) }} {{ $process->outputUnit?->code }}</td>
                                    <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($process->output_unit_cost) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-8 text-center text-gray-500">Belum ada Work In Process.</td>
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
                        <span class="font-semibold">SRM / WIP</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Masuk</span>
                        <span class="font-semibold">FG / PCS</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 dark:border-gray-800">
                        Standar hasil dipakai untuk membandingkan hasil aktual. Jika aktual lebih kecil, selisih menjadi minus; jika lebih besar, selisih menjadi plus.
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
