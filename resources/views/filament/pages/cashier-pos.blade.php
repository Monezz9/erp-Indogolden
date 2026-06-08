<x-filament-panels::page>
    <div x-data x-on:open-receipt.window="window.open($event.detail.url, '_blank')" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        <div class="space-y-5">
            <div class="overflow-hidden rounded-xl border border-red-100 bg-white shadow-sm dark:border-red-900/40 dark:bg-gray-900">
                <div class="border-b border-red-100 bg-gradient-to-r from-red-600 via-red-500 to-amber-400 px-5 py-4 text-center text-white dark:border-red-900/40">
                    <h1 class="text-2xl font-black tracking-normal">KASIR</h1>
                    <div class="mt-1 text-xs font-semibold uppercase tracking-normal text-red-50">{{ $this->branchName() }}</div>
                </div>
                <div class="grid gap-3 px-4 py-3 text-sm md:grid-cols-3">
                    <div>
                        <div class="text-xs text-gray-500">No Nota</div>
                        <div class="font-bold text-gray-950 dark:text-white">{{ $saleNumber }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Kasir</div>
                        <div class="font-bold text-gray-950 dark:text-white">{{ $cashierName ?: 'Pilih Kasir' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Total Saat Ini</div>
                        <div class="font-bold text-red-600">{{ \App\Support\IndoNumber::rupiah($this->total()) }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="grid gap-3 md:grid-cols-4">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">No Nota
                        <input type="text" wire:model="saleNumber" readonly class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 font-semibold text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                    </label>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Cabang
                        @if(auth()->user()?->isBranchLike())
                            <input type="text" value="{{ $this->branchName() }}" readonly class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 font-semibold text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                        @else
                            <select wire:model.live="branchId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="">Pilih Cabang</option>
                                @foreach($this->branchOptions() as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </label>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Kasir
                        <select wire:model.live="cashierId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Pilih Kasir</option>
                            @foreach($this->cashierOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label
                        x-data="{
                            currentTime: '',
                            formatCurrentTime() {
                                const parts = new Intl.DateTimeFormat('id-ID', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    second: '2-digit',
                                    hour12: false,
                                }).formatToParts(new Date()).reduce((values, part) => {
                                    values[part.type] = part.value;

                                    return values;
                                }, {});

                                this.currentTime = `${parts.day} ${parts.month} ${parts.year} ${parts.hour}:${parts.minute}:${parts.second}`;
                            },
                            init() {
                                this.formatCurrentTime();
                                setInterval(() => this.formatCurrentTime(), 1000);
                            },
                        }"
                        class="text-sm font-medium text-gray-700 dark:text-gray-200"
                    >Tanggal
                        <input type="text" :value="currentTime" readonly class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 font-semibold text-gray-700 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                    </label>
                </div>
            </div>

            @php($selectedItem = $this->selectedItem())
            @php($selectedDrinkItem = $this->selectedDrinkItem())

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-base font-bold text-gray-950 dark:text-white">Pesanan</h2>
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_120px_160px_auto]">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Pesanan
                        <select wire:model.live="selectedItemId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Pilih Pesanan</option>
                            @foreach($this->itemOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Qty
                        <input type="number" step="any" min="0.0001" wire:model.live="qty" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </label>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Harga
                        <input type="number" step="any" min="0" wire:model.live="unitPrice" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </label>

                    <div class="flex items-end">
                        <button type="button" wire:click="addItem" class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                            Tambah
                        </button>
                    </div>
                </div>

                <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Kategori: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $this->categoryShortLabel($selectedItem) }}</span>
                    <span class="mx-2 text-gray-300">|</span>
                    Satuan: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $selectedItem?->defaultUnit?->code ?? '-' }}</span>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-base font-bold text-gray-950 dark:text-white">Minuman</h2>
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_120px_160px_auto]">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Minuman
                        <select wire:model.live="selectedDrinkItemId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Pilih Minuman</option>
                            @foreach($this->drinkOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Qty
                        <input type="number" step="any" min="0.0001" wire:model.live="drinkQty" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </label>

                    <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Harga
                        <input type="number" step="any" min="0" wire:model.live="drinkUnitPrice" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </label>

                    <div class="flex items-end">
                        <button type="button" wire:click="addDrink" class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                            Tambah
                        </button>
                    </div>
                </div>

                <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Satuan: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $selectedDrinkItem?->defaultUnit?->code ?? '-' }}</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <table class="min-w-[820px] w-full text-sm">
                    <thead class="bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <tr>
                            <th class="w-14 px-3 py-2 text-left">No</th>
                            <th class="px-3 py-2 text-left">Barang</th>
                            <th class="w-24 px-3 py-2 text-right">Qty</th>
                            <th class="w-24 px-3 py-2 text-left">Satuan</th>
                            <th class="w-36 px-3 py-2 text-right">Harga</th>
                            <th class="w-40 px-3 py-2 text-right">Total</th>
                            <th class="w-24 px-3 py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cart as $index => $line)
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-2">{{ $index + 1 }}</td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $line['name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $line['sku'] ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-2 text-right">{{ number_format($line['qty'], 4, ',', '.') }}</td>
                                <td class="px-3 py-2">{{ $line['unit_name'] }}</td>
                                <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($line['unit_price']) }}</td>
                                <td class="px-3 py-2 text-right font-semibold">{{ \App\Support\IndoNumber::rupiah($line['line_total']) }}</td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" wire:click="removeItem({{ $index }})" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-gray-500">Keranjang masih kosong.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-base font-bold text-gray-950 dark:text-white">Ringkasan</h2>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-semibold">{{ \App\Support\IndoNumber::rupiah($this->subtotal()) }}</span>
                    </div>

                    <label class="block text-gray-700 dark:text-gray-200">Metode Bayar
                        <select wire:model="paymentMethod" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @foreach($this->paymentMethodOptions() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block text-gray-700 dark:text-gray-200">Diskon
                        <input type="number" step="any" min="0" wire:model.live="discountAmount" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </label>

                    <label class="block text-gray-700 dark:text-gray-200">Pajak
                        <input type="number" step="any" min="0" wire:model.live="taxAmount" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-right text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                    </label>

                    <label class="block text-gray-700 dark:text-gray-200">Kuah
                        <select wire:model="brothType" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @foreach($this->brothTypeOptions() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block text-gray-700 dark:text-gray-200">Level Pedas
                        <select wire:model="spiceLevel" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            @for($level = 0; $level <= 10; $level++)
                                <option value="{{ $level }}">Level {{ $level }}</option>
                            @endfor
                        </select>
                    </label>

                    <label class="block text-gray-700 dark:text-gray-200">Catatan
                        <textarea wire:model="notes" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                    </label>

                    <div class="border-t border-gray-100 pt-3 dark:border-gray-800">
                        <div class="flex items-center justify-between text-lg font-black">
                            <span>Total</span>
                            <span>{{ \App\Support\IndoNumber::rupiah($this->total()) }}</span>
                        </div>
                    </div>
                </div>

                <button type="button" wire:click="checkout" class="mt-4 w-full rounded-lg bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700">
                    Bayar
                </button>

            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h2 class="text-base font-bold text-gray-950 dark:text-white">Transaksi Terakhir</h2>

                <div class="mt-3 space-y-2">
                    @forelse($this->recentSales() as $sale)
                        <div class="rounded-lg border border-gray-100 p-3 text-sm dark:border-gray-800">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-gray-950 dark:text-white">{{ $sale->sale_number }}</span>
                                <span class="text-xs text-gray-500">{{ $sale->sale_date?->format('H:i') }}</span>
                            </div>
                            <div class="mt-1 flex items-center justify-between gap-3 text-gray-500">
                                <span>{{ $sale->branch?->name ?? '-' }}</span>
                                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ \App\Support\IndoNumber::rupiah($sale->total_amount) }}</span>
                            </div>
                            <div class="mt-2 text-right">
                                <a href="{{ route('branch-sales.print.receipt', ['branchSale' => $sale]) }}" target="_blank" class="text-xs font-semibold text-red-600 hover:text-red-700">
                                    Cetak Nota
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-4 text-sm text-gray-500">Belum ada transaksi.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
