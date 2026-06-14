<x-filament-panels::page>
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-5">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-lg font-bold text-gray-950 dark:text-white">Mulai Grooming</h2>

                @php
                    $selectedItem = $this->selectedItem();
                @endphp

                <div
                    x-data="{
                        inputQty: @entangle('inputQty').defer,
                        decimal(value) {
                            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(value || 0));
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

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-800">
                        <div class="text-gray-500">Stok Raw Dirty</div>
                        <div class="mt-1 text-lg font-bold text-gray-950 dark:text-white">
                            {{ \App\Support\IndoNumber::decimal($this->availableQty()) }} {{ $selectedItem?->defaultUnit?->code }}
                        </div>
                    </div>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Qty Masuk
                        <input type="number" step="any" min="0" x-model.number="inputQty" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </label>
                </div>

                <label class="mt-4 block text-sm font-medium text-gray-700 dark:text-gray-200">Catatan <span class="text-red-600">*</span>
                    <textarea wire:model="notes" rows="2" required class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                </label>

                <button
                    type="button"
                    x-on:click="(async () => {
                        await $wire.set('inputQty', Number(inputQty || 0));
                        await $wire.start();
                    })()"
                    class="mt-4 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                >
                    Start Grooming
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
                            <th class="px-3 py-2 text-left">Hasil SRM</th>
                            <th class="px-3 py-2 text-left">Status</th>
                            <th class="px-3 py-2 text-right">Masuk</th>
                            <th class="px-3 py-2 text-right">Hasil Aktual</th>
                            <th class="px-3 py-2 text-right">Susut</th>
                            <th class="px-3 py-2 text-right">HPP Hasil</th>
                            <th class="px-3 py-2 text-right">Aksi</th>
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
                                <td class="px-3 py-2">
                                    <span @class([
                                        'rounded-full px-2 py-1 text-xs font-semibold',
                                        'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => $process->status === 'in_progress',
                                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $process->status === 'completed',
                                        'bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => ! in_array($process->status, ['in_progress', 'completed'], true),
                                    ])>
                                        {{ $process->status === 'in_progress' ? 'In Progress' : ($process->status === 'completed' ? 'Completed' : $process->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($process->input_qty) }} {{ $process->unit?->code }}</td>
                                <td class="px-3 py-2 text-right">
                                    @if($process->status === 'in_progress')
                                        <input type="number" step="any" min="0" wire:model="completionQty.{{ $process->id }}" class="w-28 rounded-lg border border-gray-300 bg-gray-50 px-2 py-1 text-right text-gray-950 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                                    @else
                                        {{ \App\Support\IndoNumber::decimal($process->output_qty) }} {{ $process->unit?->code }}
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">{{ $process->status === 'completed' ? \App\Support\IndoNumber::decimal($process->shrinkage_qty).' '.($process->unit?->code ?? '') : '-' }}</td>
                                <td class="px-3 py-2 text-right">{{ $process->status === 'completed' ? \App\Support\IndoNumber::rupiah($process->output_unit_cost) : '-' }}</td>
                                <td class="px-3 py-2 text-right">
                                    @if($process->status === 'in_progress')
                                        <button type="button" wire:click="complete({{ $process->id }})" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                            Complete
                                        </button>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-3 py-8 text-center text-gray-500">Belum ada proses pembersihan.</td>
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
                    <span class="font-semibold">SRM saat complete</span>
                </div>
                <div class="border-t border-gray-100 pt-3 dark:border-gray-800">
                    Start Grooming hanya mengurangi RM. SRM, susut, dan HPP hasil baru tercatat saat Complete Grooming.
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
