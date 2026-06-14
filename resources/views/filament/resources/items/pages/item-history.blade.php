<x-filament-panels::page>
    @php
        $item = $this->item();
        $rows = $this->historyRows();
        $timelineRows = $this->timelineRows();
        $summary = $this->summary();
        $detail = $this->selectedHistoryRow();
    @endphp

    <div class="space-y-5">
        <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-950 dark:text-white">{{ $item->sku }} - {{ $item->name }}</h2>
                    <p class="mt-1 text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ $item->category?->name ?? '-' }} &bull; {{ $item->defaultUnit?->name ?? $item->defaultUnit?->code ?? '-' }}
                    </p>
                </div>
                <div class="grid gap-3 text-sm sm:grid-cols-3 lg:min-w-[560px]">
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Stok Saat Ini</div>
                        <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ \App\Support\IndoNumber::decimal($summary['current_stock']) }} {{ $summary['stock_unit'] }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">HPP Terakhir</div>
                        <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ \App\Support\IndoNumber::rupiah($summary['latest_hpp']) }}/{{ $summary['stock_unit'] }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800">
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Aktivitas Terakhir</div>
                        <div class="mt-1 font-bold text-gray-950 dark:text-white">{{ $summary['latest_activity_at']?->format('d M Y') ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <x-filament::icon icon="heroicon-o-cube" class="h-5 w-5" />
                    </span>
                    <div>
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Stok Saat Ini</div>
                        <div class="text-lg font-bold text-gray-950 dark:text-white">{{ \App\Support\IndoNumber::decimal($summary['current_stock']) }} {{ $summary['stock_unit'] }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-5 w-5" />
                    </span>
                    <div>
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Total Masuk</div>
                        <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ \App\Support\IndoNumber::decimal($summary['total_in']) }} {{ $summary['stock_unit'] }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                        <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-5 w-5" />
                    </span>
                    <div>
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Total Keluar</div>
                        <div class="text-lg font-bold text-red-700 dark:text-red-300">{{ \App\Support\IndoNumber::decimal($summary['total_out']) }} {{ $summary['stock_unit'] }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                        <x-filament::icon icon="heroicon-o-banknotes" class="h-5 w-5" />
                    </span>
                    <div>
                        <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">HPP Terakhir</div>
                        <div class="text-lg font-bold text-gray-950 dark:text-white">{{ \App\Support\IndoNumber::rupiah($summary['latest_hpp']) }}/{{ $summary['stock_unit'] }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
            <div class="grid gap-3 lg:grid-cols-[220px_1fr_1fr_1.6fr]">
                <label class="ig-procurement-field">
                    <span>Aktivitas</span>
                    <select wire:model.live="movementType">
                        @foreach($this->movementTypeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="ig-procurement-field">
                    <span>Dari</span>
                    <input type="date" wire:model.live="dateFrom" />
                </label>
                <label class="ig-procurement-field">
                    <span>Sampai</span>
                    <input type="date" wire:model.live="dateTo" />
                </label>
                <label class="ig-procurement-field">
                    <span>Search</span>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari referensi atau catatan..." />
                </label>
            </div>
        </section>

        @if($rows->isEmpty())
            <section class="rounded-lg bg-white px-6 py-16 text-center shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="mx-auto grid h-14 w-14 place-items-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-300">
                    <x-filament::icon icon="heroicon-o-cube" class="h-8 w-8" />
                </div>
                <h3 class="mt-4 text-base font-bold text-gray-950 dark:text-white">Belum ada histori barang.</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aktivitas pengadaan, produksi, dan pergerakan stok akan muncul di sini.</p>
            </section>
        @else
            <section class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-950 dark:text-white">Timeline Aktivitas Terbaru</h3>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">10 transaksi terakhir</span>
                </div>
                <div class="space-y-0">
                    @foreach($timelineRows as $row)
                        @php
                            $tone = $row['activity_tone'];
                            $toneClasses = match ($tone) {
                                'in' => 'bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
                                'out' => 'bg-red-50 text-red-700 ring-red-100 dark:bg-red-500/10 dark:text-red-300 dark:ring-red-500/20',
                                default => 'bg-orange-50 text-orange-700 ring-orange-100 dark:bg-orange-500/10 dark:text-orange-300 dark:ring-orange-500/20',
                            };
                            $qtyClass = match ($tone) {
                                'in' => 'text-emerald-700 dark:text-emerald-300',
                                'out' => 'text-red-700 dark:text-red-300',
                                default => 'text-orange-700 dark:text-orange-300',
                            };
                        @endphp
                        <article class="relative border-l border-gray-200 pb-6 pl-5 last:border-transparent last:pb-0 dark:border-gray-800">
                            <span class="absolute -left-2 top-0 h-4 w-4 rounded-full ring-4 {{ $toneClasses }}"></span>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="font-bold text-gray-950 dark:text-white">{{ $row['movement_label'] }}</div>
                                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $row['date']?->format('d M Y H:i') ?? '-' }} &bull; {{ $row['reference'] }}
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $row['notes'] ?: '-' }}</p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <div class="text-lg font-black {{ $qtyClass }}">{{ $row['qty_label'] }}</div>
                                    <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">Saldo: {{ \App\Support\IndoNumber::decimal($row['balance_after']) }} {{ $row['unit_code'] }}</div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="hidden overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800 md:block">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold">Aktivitas</th>
                                <th class="px-4 py-3 text-right font-semibold">Qty</th>
                                <th class="px-4 py-3 text-right font-semibold">Saldo</th>
                                <th class="px-4 py-3 text-left font-semibold">Referensi</th>
                                <th class="px-4 py-3 text-left font-semibold">User</th>
                                <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows->reverse()->values() as $row)
                                @php
                                    $qtyClass = match ($row['activity_tone']) {
                                        'in' => 'text-emerald-700 dark:text-emerald-300',
                                        'out' => 'text-red-700 dark:text-red-300',
                                        default => 'text-orange-700 dark:text-orange-300',
                                    };
                                @endphp
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-4 py-3">{{ $row['date']?->format('d/m/y H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $row['movement_label'] }}</td>
                                    <td class="px-4 py-3 text-right font-bold {{ $qtyClass }}">{{ $row['qty_label'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ \App\Support\IndoNumber::decimal($row['balance_after']) }} {{ $row['unit_code'] }}</td>
                                    <td class="px-4 py-3">{{ $row['reference'] }}</td>
                                    <td class="px-4 py-3">{{ $row['user'] }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" wire:click="showHistoryDetail({{ $row['id'] }})" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="space-y-3 md:hidden">
                @foreach($rows->reverse()->values() as $row)
                    @php
                        $qtyClass = match ($row['activity_tone']) {
                            'in' => 'text-emerald-700 dark:text-emerald-300',
                            'out' => 'text-red-700 dark:text-red-300',
                            default => 'text-orange-700 dark:text-orange-300',
                        };
                    @endphp
                    <article class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-bold text-gray-950 dark:text-white">{{ $row['movement_label'] }}</div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $row['date']?->format('d/m/y H:i') ?? '-' }} &bull; {{ $row['reference'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-black {{ $qtyClass }}">{{ $row['qty_label'] }}</div>
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">Saldo {{ \App\Support\IndoNumber::decimal($row['balance_after']) }} {{ $row['unit_code'] }}</div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $row['user'] }}</span>
                            <button type="button" wire:click="showHistoryDetail({{ $row['id'] }})" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:text-gray-200">
                                Detail
                            </button>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </div>

    @if($detail)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4">
            <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-bold text-gray-950 dark:text-white">Detail Transaksi</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $detail['movement_label'] }} &bull; {{ $detail['reference'] }}</p>
                    </div>
                    <button type="button" wire:click="closeHistoryDetail" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>
                <div class="grid gap-3 p-5 sm:grid-cols-2">
                    <div><span class="text-xs font-semibold uppercase text-gray-500">Jenis Transaksi</span><div class="font-semibold text-gray-950 dark:text-white">{{ $detail['movement_label'] }}</div></div>
                    <div><span class="text-xs font-semibold uppercase text-gray-500">Tanggal</span><div class="font-semibold text-gray-950 dark:text-white">{{ $detail['date']?->format('d M Y H:i') ?? '-' }}</div></div>
                    <div><span class="text-xs font-semibold uppercase text-gray-500">Referensi</span><div class="font-semibold text-gray-950 dark:text-white">{{ $detail['reference'] }}</div></div>
                    <div><span class="text-xs font-semibold uppercase text-gray-500">Qty</span><div class="font-semibold text-gray-950 dark:text-white">{{ $detail['qty_label'] }}</div></div>
                    <div><span class="text-xs font-semibold uppercase text-gray-500">Saldo Setelah Transaksi</span><div class="font-semibold text-gray-950 dark:text-white">{{ \App\Support\IndoNumber::decimal($detail['balance_after']) }} {{ $detail['unit_code'] }}</div></div>
                    <div><span class="text-xs font-semibold uppercase text-gray-500">HPP Saat Transaksi</span><div class="font-semibold text-gray-950 dark:text-white">{{ $detail['unit_cost'] > 0 ? \App\Support\IndoNumber::rupiah($detail['unit_cost']).'/'.$detail['unit_code'] : '-' }}</div></div>
                    <div><span class="text-xs font-semibold uppercase text-gray-500">User</span><div class="font-semibold text-gray-950 dark:text-white">{{ $detail['user'] }}</div></div>
                    <div><span class="text-xs font-semibold uppercase text-gray-500">Catatan</span><div class="font-semibold text-gray-950 dark:text-white">{{ $detail['notes'] ?: '-' }}</div></div>
                    @foreach($detail['reference_details'] as $label => $value)
                        <div><span class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</span><div class="font-semibold text-gray-950 dark:text-white">{{ $value }}</div></div>
                    @endforeach
                </div>
                <div class="flex justify-end border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                    <button type="button" wire:click="closeHistoryDetail" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-950">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
