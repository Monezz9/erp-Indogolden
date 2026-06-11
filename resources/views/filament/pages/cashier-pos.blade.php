<x-filament-panels::page>
    @php($selectedItem = $this->selectedItem())
    @php($selectedDrinkItem = $this->selectedDrinkItem())
    @php($menuItems = $this->menuItems())
    @php($drinkItems = $this->drinkItems())
    @php($hasSeblakSelection = filled($selectedItem))
    @php($hasDrinkSelection = filled($selectedDrinkItem))
    @php($hasSelection = $hasSeblakSelection || $hasDrinkSelection)
    @php($activePrice = $hasDrinkSelection ? $drinkUnitPrice : $unitPrice)

    <div
        x-data
        x-on:open-receipt.window="window.open($event.detail.url, '_blank')"
        class="min-h-screen w-full max-w-none bg-gray-100 text-gray-950 dark:bg-gray-950 dark:text-white"
    >
        <div class="w-full max-w-none px-4 py-4">
            <header class="rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <h1 class="text-[20px] font-black leading-tight tracking-normal text-gray-950 dark:text-white">Kasir Head Office</h1>
                        <div class="mt-2 flex flex-wrap gap-2 text-[13px] font-bold text-gray-600 dark:text-gray-300">
                            <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 dark:border-gray-800 dark:bg-gray-950">Nota {{ $saleNumber }}</span>
                            <span class="rounded-full border border-gray-200 bg-gray-50 px-3 py-1 dark:border-gray-800 dark:bg-gray-950">Kasir {{ $cashierName ?: 'Pilih Kasir' }}</span>
                            <span class="rounded-full border border-red-100 bg-red-50 px-3 py-1 text-red-600 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-100">{{ \App\Support\IndoNumber::rupiah($this->total()) }}</span>
                        </div>
                    </div>

                    <div class="grid gap-2 md:grid-cols-3">
                        <label class="text-[13px] font-bold text-gray-700 dark:text-gray-200">Cabang
                            @if(auth()->user()?->isBranchLike())
                                <input type="text" value="{{ $this->branchName() }}" readonly class="mt-1 block h-10 w-full rounded-xl border border-gray-200 bg-gray-100 px-3 text-[13px] font-bold text-gray-700 shadow-sm dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200" />
                            @else
                                <select wire:model.live="branchId" class="mt-1 block h-10 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-[13px] font-bold text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-800 dark:bg-gray-950 dark:text-white">
                                    <option value="">Pilih Cabang</option>
                                    @foreach($this->branchOptions() as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </label>

                        <label class="text-[13px] font-bold text-gray-700 dark:text-gray-200">Kasir
                            <select wire:model.live="cashierId" class="mt-1 block h-10 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-[13px] font-bold text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-800 dark:bg-gray-950 dark:text-white">
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
                            class="text-[13px] font-bold text-gray-700 dark:text-gray-200"
                        >Tanggal
                            <input type="text" :value="currentTime" readonly class="mt-1 block h-10 w-full rounded-xl border border-gray-200 bg-gray-100 px-3 text-[13px] font-bold text-gray-700 shadow-sm dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200" />
                        </label>
                    </div>
                </div>
            </header>

            <main class="mt-4 grid gap-4 xl:grid-cols-[380px_minmax(0,1fr)_420px]">
                <section x-data="{ search: '' }" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-100 p-4 dark:border-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-[19px] font-black leading-tight text-gray-950 dark:text-white">Daftar Menu</h2>
                                <p class="mt-1 text-[13px] font-semibold text-gray-500">{{ $menuItems->count() + $drinkItems->count() }} menu tersedia</p>
                            </div>
                            <span class="rounded-full bg-red-50 px-3 py-1 text-[13px] font-black text-red-600 dark:bg-red-950/40 dark:text-red-100">POS</span>
                        </div>

                        <label class="mt-3 block">
                            <span class="sr-only">Cari menu</span>
                            <input
                                x-model="search"
                                type="search"
                                placeholder="Cari menu seblak atau minuman"
                                class="block h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-[14px] font-semibold text-gray-950 shadow-sm placeholder:text-gray-400 focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-800 dark:bg-gray-950 dark:text-white"
                            />
                        </label>
                    </div>

                    <div class="h-[calc(100vh-250px)] overflow-y-auto">
                        <table class="w-full text-[13px]">
                            <thead class="sticky top-0 z-10 border-b border-gray-100 bg-gray-50 text-[11px] uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
                                <tr>
                                    <th class="w-12 px-4 py-2.5 text-left">No</th>
                                    <th class="px-2 py-2.5 text-left">Nama Menu</th>
                                    <th class="w-28 px-4 py-2.5 text-right">Harga</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse($menuItems as $item)
                                    <tr
                                        wire:click="addMenuItemToCart({{ $item->id }})"
                                        data-search="{{ \Illuminate\Support\Str::lower($item->name.' '.$this->categoryShortLabel($item).' '.($item->defaultUnit?->code ?? '')) }}"
                                        x-show="$el.dataset.search.includes(search.toLowerCase())"
                                        @class([
                                            'h-[62px] cursor-pointer border-l-4 border-transparent transition hover:bg-red-50 dark:hover:bg-red-950/25',
                                            '!border-red-500 bg-red-50 dark:bg-red-950/35' => $selectedItemId === $item->id,
                                        ])
                                    >
                                        <td class="px-4 py-2 font-black text-gray-400">{{ $loop->iteration }}</td>
                                        <td class="px-2 py-2">
                                            <div class="line-clamp-1 font-black text-gray-950 dark:text-white">{{ $item->name }}</div>
                                            <div class="mt-0.5 line-clamp-1 text-[12px] font-bold text-gray-500">{{ $this->categoryShortLabel($item) }} &bull; {{ $item->defaultUnit?->code ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-right font-black text-red-600">{{ \App\Support\IndoNumber::rupiah((float) $item->selling_price) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-5 text-center text-[13px] font-semibold text-gray-500">Menu seblak belum tersedia.</td>
                                    </tr>
                                @endforelse

                                @if($drinkItems->isNotEmpty())
                                    <tr>
                                        <td colspan="3" class="bg-gray-50 px-4 py-2 text-[11px] font-black uppercase text-gray-500 dark:bg-gray-950 dark:text-gray-400">Minuman</td>
                                    </tr>
                                    @foreach($drinkItems as $item)
                                        <tr
                                        wire:click="addDrinkItemToCart({{ $item->id }})"
                                            data-search="{{ \Illuminate\Support\Str::lower($item->name.' '.$this->categoryShortLabel($item).' '.($item->defaultUnit?->code ?? '')) }}"
                                            x-show="$el.dataset.search.includes(search.toLowerCase())"
                                            @class([
                                                'h-[62px] cursor-pointer border-l-4 border-transparent transition hover:bg-red-50 dark:hover:bg-red-950/25',
                                                '!border-red-500 bg-red-50 dark:bg-red-950/35' => $selectedDrinkItemId === $item->id,
                                            ])
                                        >
                                            <td class="px-4 py-2 font-black text-gray-400">{{ $loop->iteration }}</td>
                                            <td class="px-2 py-2">
                                                <div class="line-clamp-1 font-black text-gray-950 dark:text-white">{{ $item->name }}</div>
                                                <div class="mt-0.5 line-clamp-1 text-[12px] font-bold text-gray-500">{{ $this->categoryShortLabel($item) }} &bull; {{ $item->defaultUnit?->code ?? '-' }}</div>
                                            </td>
                                            <td class="px-4 py-2 text-right font-black text-red-600">{{ \App\Support\IndoNumber::rupiah((float) $item->selling_price) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-[20px] font-black leading-tight text-gray-950 dark:text-white">Custom Seblak</h2>
                            <p class="mt-1 text-[14px] font-bold text-gray-500">Atur pilihan seblak setelah menu masuk keranjang.</p>
                        </div>
                        <div class="rounded-2xl border border-red-100 bg-red-50 px-4 py-2 text-right dark:border-red-900/50 dark:bg-red-950/35">
                            <div class="text-[11px] font-black uppercase text-red-500">Aktif</div>
                            <div class="text-[14px] font-black leading-tight text-red-600 dark:text-red-100">Untuk Seblak</div>
                        </div>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                            <div class="mb-3 text-[14px] font-black uppercase text-gray-900 dark:text-gray-100">Kuah</div>
                            <div class="grid grid-cols-3 gap-3">
                                @foreach($this->brothTypeOptions() as $key => $label)
                                    <label @class(['flex min-h-[52px] cursor-pointer items-center justify-center rounded-full border px-3 text-center text-[14px] font-black shadow-sm transition hover:border-red-300 hover:bg-red-50', 'border-red-500 bg-red-50 text-red-600' => $brothType === $key, 'border-gray-200 bg-white text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200' => $brothType !== $key])>
                                        <input type="radio" wire:model.live="brothType" value="{{ $key }}" class="sr-only" />
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                            <div class="mb-3 text-[14px] font-black uppercase text-gray-900 dark:text-gray-100">Level</div>
                            <div class="grid grid-cols-4 gap-3">
                                @foreach($this->spiceLevelOptions() as $key => $label)
                                    <label @class(['flex min-h-[52px] cursor-pointer items-center justify-center rounded-full border px-2 text-center text-[14px] font-black shadow-sm transition hover:border-red-300 hover:bg-red-50', 'border-red-500 bg-red-50 text-red-600' => $spiceLevel === $key, 'border-gray-200 bg-white text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200' => $spiceLevel !== $key])>
                                        <input type="radio" wire:model.live="spiceLevel" value="{{ $key }}" class="sr-only" />
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-4 2xl:grid-cols-2">
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                                <div class="mb-3 text-[14px] font-black uppercase text-gray-900 dark:text-gray-100">Rasa</div>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach($this->tasteTypeOptions() as $key => $label)
                                        <label @class(['flex min-h-[52px] cursor-pointer items-center justify-center rounded-full border px-3 text-center text-[14px] font-black shadow-sm transition hover:border-red-300 hover:bg-red-50', 'border-red-500 bg-red-50 text-red-600' => $tasteType === $key, 'border-gray-200 bg-white text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200' => $tasteType !== $key])>
                                            <input type="radio" wire:model.live="tasteType" value="{{ $key }}" class="sr-only" />
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                                <div class="mb-3 text-[14px] font-black uppercase text-gray-900 dark:text-gray-100">Telur</div>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach($this->eggChoiceOptions() as $key => $label)
                                        <label @class(['flex min-h-[52px] cursor-pointer items-center justify-center rounded-full border px-3 text-center text-[14px] font-black shadow-sm transition hover:border-red-300 hover:bg-red-50', 'border-red-500 bg-red-50 text-red-600' => $eggChoice === $key, 'border-gray-200 bg-white text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200' => $eggChoice !== $key])>
                                            <input type="radio" wire:model.live="eggChoice" value="{{ $key }}" class="sr-only" />
                                            {{ str_replace('Telur ', '', $label) }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <label class="mt-4 block rounded-2xl border border-gray-200 bg-gray-50 p-4 text-[14px] font-black uppercase text-gray-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200">Catatan Pesanan
                        <textarea wire:model="notes" rows="2" class="mt-2 block w-full resize-none rounded-xl border border-gray-200 bg-white px-3 py-2 text-[14px] font-semibold text-gray-950 shadow-sm focus:border-red-500 focus:ring-2 focus:ring-red-500/20 dark:border-gray-800 dark:bg-gray-900 dark:text-white"></textarea>
                    </label>
                </section>

                <aside class="xl:sticky xl:top-4 xl:self-start">
                    <div class="space-y-4">
                        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center justify-between gap-3 border-b border-gray-100 p-4 dark:border-gray-800">
                                <div>
                                    <h2 class="text-[19px] font-black leading-tight text-gray-950 dark:text-white">Keranjang</h2>
                                    <p class="mt-1 text-[13px] font-bold text-gray-500">{{ count($cart) }} item</p>
                                </div>
                                @if($cart !== [])
                                    <button type="button" wire:click="clearCart" class="rounded-xl border border-gray-200 px-3 py-2 text-[13px] font-black text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-200 dark:hover:bg-gray-950">Kosongkan</button>
                                @endif
                            </div>

                            <div class="max-h-[310px] overflow-y-auto p-3">
                                @forelse($cart as $index => $line)
                                    <div class="mb-2 rounded-xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="truncate text-[14px] font-black text-gray-950 dark:text-white">{{ $line['name'] }}</div>
                                                <div class="mt-1 text-[12px] font-bold text-gray-500">{{ $line['unit_name'] }} x {{ \App\Support\IndoNumber::rupiah($line['unit_price']) }}</div>
                                            </div>
                                            <button type="button" wire:click="removeItem({{ $index }})" class="shrink-0 rounded-lg border border-gray-200 px-2 py-1 text-[12px] font-black text-gray-600 hover:bg-white dark:border-gray-800 dark:text-gray-200 dark:hover:bg-gray-900">Hapus</button>
                                        </div>
                                        <div class="mt-3 flex items-center justify-between gap-3">
                                            <div class="inline-flex h-9 items-center overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                                                <button
                                                    type="button"
                                                    wire:click="decrementCartItem({{ $index }})"
                                                    class="flex h-9 w-9 items-center justify-center text-lg font-black leading-none text-gray-600 transition hover:bg-red-50 hover:text-red-600 active:bg-red-100 dark:text-gray-200 dark:hover:bg-red-950/30"
                                                    aria-label="Kurangi jumlah {{ $line['name'] }}"
                                                >
                                                    -
                                                </button>
                                                <div class="flex h-9 min-w-10 items-center justify-center border-x border-gray-200 px-2 text-[13px] font-black text-gray-950 dark:border-gray-800 dark:text-white">
                                                    {{ rtrim(rtrim(number_format($line['qty'], 2, ',', '.'), '0'), ',') }}
                                                </div>
                                                <button
                                                    type="button"
                                                    wire:click="incrementCartItem({{ $index }})"
                                                    class="flex h-9 w-9 items-center justify-center text-lg font-black leading-none text-gray-600 transition hover:bg-red-50 hover:text-red-600 active:bg-red-100 dark:text-gray-200 dark:hover:bg-red-950/30"
                                                    aria-label="Tambah jumlah {{ $line['name'] }}"
                                                >
                                                    +
                                                </button>
                                            </div>

                                            <div class="text-right text-[15px] font-black text-red-600">{{ \App\Support\IndoNumber::rupiah($line['line_total']) }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-gray-300 px-3 py-5 text-center text-[13px] font-bold text-gray-500 dark:border-gray-800">
                                        Keranjang kosong.
                                    </div>
                                @endforelse
                            </div>
                        </section>

                        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <h2 class="text-[19px] font-black leading-tight text-gray-950 dark:text-white">Pembayaran</h2>

                            <div class="mt-3 space-y-3 text-[14px]">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-gray-500">Subtotal</span>
                                    <span class="font-black text-gray-950 dark:text-white">{{ \App\Support\IndoNumber::rupiah($this->subtotal()) }}</span>
                                </div>

                                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950">
                                    <div class="text-[12px] font-black uppercase text-gray-500">Custom Seblak</div>
                                    <div class="mt-1 text-[14px] font-black leading-snug text-gray-950 dark:text-white">{{ $this->customSeblakSummary() }}</div>
                                </div>

                                <div>
                                    <div class="mb-2 font-black text-gray-700 dark:text-gray-200">Metode Bayar</div>
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach([
                                            'cash' => ['label' => 'Tunai', 'icon' => 'Rp'],
                                            'qris' => ['label' => 'QRIS', 'icon' => 'QR'],
                                            'debit' => ['label' => 'Debit / EDC', 'icon' => 'EDC'],
                                        ] as $key => $method)
                                            <label @class([
                                                'group flex h-14 cursor-pointer flex-col items-center justify-center rounded-xl border px-2 text-center text-[12px] font-black leading-tight shadow-sm transition hover:border-red-300 hover:bg-red-50',
                                                'border-red-500 bg-red-50 text-red-600' => $paymentMethod === $key,
                                                'border-gray-200 bg-white text-gray-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200' => $paymentMethod !== $key,
                                            ])>
                                                <input type="radio" wire:model.live="paymentMethod" value="{{ $key }}" class="sr-only" />
                                                <span class="mb-1 flex h-5 min-w-5 items-center justify-center rounded-md bg-gray-100 px-1 text-[10px] font-black text-gray-600 group-hover:bg-red-100 dark:bg-gray-900 dark:text-gray-300">{{ $method['icon'] }}</span>
                                                <span>{{ $method['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <label class="block font-black text-gray-700 dark:text-gray-200">Diskon
                                        <input type="number" step="any" min="0" wire:model.live="discountAmount" class="mt-1 block h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-right text-[14px] font-bold text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-800 dark:bg-gray-950 dark:text-white" />
                                    </label>

                                    <label class="block font-black text-gray-700 dark:text-gray-200">Pajak
                                        <input type="number" step="any" min="0" wire:model.live="taxAmount" class="mt-1 block h-11 w-full rounded-xl border border-gray-200 bg-gray-50 px-3 text-right text-[14px] font-bold text-gray-950 shadow-sm focus:border-red-500 focus:bg-white focus:ring-2 focus:ring-red-500/20 dark:border-gray-800 dark:bg-gray-950 dark:text-white" />
                                    </label>
                                </div>

                                <div class="rounded-2xl border border-red-100 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/35">
                                    <div class="text-[12px] font-black uppercase text-red-500">Total Bayar</div>
                                    <div class="mt-1 text-[32px] font-black leading-none text-red-600 dark:text-red-100">{{ \App\Support\IndoNumber::rupiah($this->total()) }}</div>
                                </div>
                            </div>

                            <button type="button" wire:click="checkout" class="mt-4 h-14 w-full rounded-xl bg-red-600 px-4 text-[17px] font-black uppercase tracking-normal text-white shadow-sm transition hover:bg-red-700">
                                BAYAR
                            </button>
                        </section>
                    </div>
                </aside>
            </main>
        </div>
    </div>
</x-filament-panels::page>
