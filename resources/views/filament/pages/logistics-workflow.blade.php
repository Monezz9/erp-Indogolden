<x-filament-panels::page>
    @php
        $stages = $this->workflowStages();
        $stats = $this->statSummary();
        $rawRows = $this->stockPreview('raw_dirty');
        $sortedRows = $this->stockPreview('raw_clean');
        $wipRows = $this->stockPreview('wip');
        $fgRows = $this->stockPreview('finished_goods');
    @endphp

    <style>
        .lw-hero { background: linear-gradient(120deg, rgba(127, 29, 29, 0.92), rgba(185, 28, 28, 0.82)), url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1600&q=60') center/cover no-repeat; }
        .lw-float { animation: lw-float 4s ease-in-out infinite; }
        .lw-flow-arrow { animation: lw-pulse 1.4s ease-in-out infinite; }
        .lw-card:hover { transform: translateY(-4px); }
        .lw-grid-flow { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .lw-glass { background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); }
        @keyframes lw-float { 0%,100% { transform: translateY(0);} 50% { transform: translateY(-5px);} }
        @keyframes lw-pulse { 0%,100% { opacity: 1; transform: translateX(0);} 50% { opacity: .5; transform: translateX(4px);} }
        @media (max-width: 1024px) { .lw-grid-flow { grid-template-columns: repeat(1, minmax(0, 1fr)); } }
    </style>

    <section class="lw-hero rounded-2xl p-8 text-white shadow-2xl">
        <div class="grid gap-6 lg:grid-cols-2">
            <div>
                <p class="text-xs tracking-[0.2em] text-red-100">SIBOCAH KENCUR</p>
                <h1 class="mt-2 text-4xl font-black">ALUR LOGISTIK</h1>
                <p class="mt-3 max-w-xl text-red-50">Alur kerja persediaan, produksi, dan distribusi yang mudah dipantau.</p>
                <div class="mt-5 flex items-center gap-3 text-red-100">
                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs">Gudang</span>
                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs">Produksi</span>
                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs">Distribusi Cabang</span>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="lw-glass lw-float rounded-xl border border-white/30 p-4">
                    <div class="text-sm text-red-100">Total Bahan Mentah</div>
                    <div class="mt-1 text-2xl font-bold">{{ \App\Support\IndoNumber::decimal($stats['total_raw']) }}</div>
                </div>
                <div class="lw-glass lw-float rounded-xl border border-white/30 p-4" style="animation-delay:.15s;">
                    <div class="text-sm text-red-100">Total WIP</div>
                    <div class="mt-1 text-2xl font-bold">{{ \App\Support\IndoNumber::decimal($stats['total_wip']) }}</div>
                </div>
                <div class="lw-glass lw-float rounded-xl border border-white/30 p-4" style="animation-delay:.3s;">
                    <div class="text-sm text-red-100">Total Barang Jadi</div>
                    <div class="mt-1 text-2xl font-bold">{{ \App\Support\IndoNumber::decimal($stats['total_fg']) }}</div>
                </div>
                <div class="lw-glass lw-float rounded-xl border border-white/30 p-4" style="animation-delay:.45s;">
                    <div class="text-sm text-red-100">Siap Distribusi</div>
                    <div class="mt-1 text-2xl font-bold">{{ \App\Support\IndoNumber::decimal($stats['distribution_ready']) }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-8 rounded-2xl border border-red-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Flowchart Alur Utama</h2>
        <div class="lw-grid-flow mt-5 grid gap-4">
            @foreach($stages as $index => $stage)
                <div class="relative transition-all duration-200 lw-card rounded-2xl border border-red-100 bg-gradient-to-br {{ $stage['color'] }} p-4 text-white shadow-lg">
                    <div class="text-xs text-red-100">Tahap {{ $index + 1 }}</div>
                    <div class="mt-1 text-lg font-extrabold">{{ $stage['title'] }}</div>
                    <div class="text-xs text-red-100">{{ $stage['code'] }}</div>
                    <p class="mt-2 text-xs leading-5 text-white/90">{{ $stage['desc'] }}</p>
                    @if($index < count($stages) - 1)
                        <div class="lw-flow-arrow absolute -right-3 top-1/2 hidden -translate-y-1/2 text-2xl lg:block">→</div>
                    @endif
                    <div class="mt-3 text-[11px] uppercase tracking-wider text-red-100">{{ $stage['flow'] }}</div>
                </div>
            @endforeach
        </div>
        <div class="mt-5 grid gap-3 md:grid-cols-2">
            <div class="rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
                <span class="font-semibold">MAINTENANCE, REPAIR, AND OPERATIONAL (MRO)</span> berfungsi sebagai pendukung operasional stok.
            </div>
            <div class="rounded-xl border border-red-100 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
                <span class="font-semibold">ANALYSIS</span> mencatat usage item non-satuan untuk evaluasi konsumsi dan efisiensi.
            </div>
        </div>
    </section>

    <section class="mt-8 grid gap-4 xl:grid-cols-2">
        <div class="rounded-2xl border border-red-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">BAHAN MENTAH (RM)</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Bahan baku utama mentah yang belum diolah dan masih kotor.</p>
            <ul class="mt-3 grid gap-1 text-sm text-red-700 dark:text-red-300">
                @foreach($this->rawItems() as $item)
                    <li>• {{ $item }}</li>
                @endforeach
            </ul>
            <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-3 py-2 text-left">SKU</th><th class="px-3 py-2 text-left">Item</th><th class="px-3 py-2 text-right">Qty</th><th class="px-3 py-2">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rawRows as $row)
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-2">{{ $row['sku'] }}</td><td class="px-3 py-2">{{ $row['item'] }}</td><td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row['qty']) }}</td><td class="px-3 py-2 text-center">{{ $row['unit'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-red-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">BAHAN MENTAH SORTIR (SRM)</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Bahan baku mentah yang sudah melalui proses pembersihan dan siap diproses.</p>
            <ul class="mt-3 grid gap-1 text-sm text-red-700 dark:text-red-300">
                @foreach($this->sortedItems() as $item)
                    <li>• {{ $item }}</li>
                @endforeach
            </ul>
            <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="px-3 py-2 text-left">SKU</th><th class="px-3 py-2 text-left">Item</th><th class="px-3 py-2 text-right">Qty</th><th class="px-3 py-2">Unit</th></tr></thead>
                    <tbody>
                        @foreach($sortedRows as $row)
                            <tr class="border-t border-gray-100 dark:border-gray-800"><td class="px-3 py-2">{{ $row['sku'] }}</td><td class="px-3 py-2">{{ $row['item'] }}</td><td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row['qty']) }}</td><td class="px-3 py-2 text-center">{{ $row['unit'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-red-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">PROSES PRODUKSI (WIP)</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Barang atau bahan dalam tahap produksi namun belum menjadi produk jadi.</p>
            <ul class="mt-3 grid gap-1 text-sm text-red-700 dark:text-red-300">
                @foreach($this->wipItems() as $item)
                    <li>• {{ $item }}</li>
                @endforeach
            </ul>
            <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="px-3 py-2 text-left">SKU</th><th class="px-3 py-2 text-left">Item</th><th class="px-3 py-2 text-right">Qty</th><th class="px-3 py-2">Unit</th></tr></thead>
                    <tbody>
                        @foreach($wipRows as $row)
                            <tr class="border-t border-gray-100 dark:border-gray-800"><td class="px-3 py-2">{{ $row['sku'] }}</td><td class="px-3 py-2">{{ $row['item'] }}</td><td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row['qty']) }}</td><td class="px-3 py-2 text-center">{{ $row['unit'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-red-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">BARANG JADI (FG)</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Produk selesai diproduksi dan siap didistribusikan.</p>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-red-100 bg-red-50 p-3 text-sm dark:border-red-900/50 dark:bg-red-950/30">
                    <div class="font-semibold text-red-800 dark:text-red-200">Produk Tanpa Proses</div>
                    <div class="mt-1 text-red-700 dark:text-red-300">All Frozen, All Keringan</div>
                </div>
                <div class="rounded-xl border border-red-100 bg-red-50 p-3 text-sm dark:border-red-900/50 dark:bg-red-950/30">
                    <div class="font-semibold text-red-800 dark:text-red-200">Produk Dengan Proses</div>
                    <div class="mt-1 text-red-700 dark:text-red-300">All Sayur, Paket Minuman</div>
                </div>
            </div>
            <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="px-3 py-2 text-left">SKU</th><th class="px-3 py-2 text-left">Item</th><th class="px-3 py-2 text-right">Qty</th><th class="px-3 py-2">Status</th></tr></thead>
                    <tbody>
                        @foreach($fgRows as $row)
                            <tr class="border-t border-gray-100 dark:border-gray-800"><td class="px-3 py-2">{{ $row['sku'] }}</td><td class="px-3 py-2">{{ $row['item'] }}</td><td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row['qty']) }}</td><td class="px-3 py-2"><span class="rounded-full bg-emerald-100 px-2 py-1 text-[11px] text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">Siap</span></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="mt-8 rounded-2xl border border-red-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Integrasi INDOGOLDEN</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">FG → System Input → Branch Distribution dengan visibility realtime untuk gudang dan cabang.</p>
        <div class="mt-3 grid gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-700"><div class="font-semibold">AI Insight</div><div class="text-gray-500">Prediksi kebutuhan cabang besok.</div></div>
            <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-700"><div class="font-semibold">IoT Sync</div><div class="text-gray-500">Stok update terintegrasi dari proses produksi.</div></div>
            <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-700"><div class="font-semibold">Blockchain-ready</div><div class="text-gray-500">Audit trail distribusi transparan.</div></div>
        </div>
    </section>

    <section class="mt-8 rounded-2xl border border-red-100 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">TABEL ALUR LOGISTIK</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Stok Mentah Kotor → Proses Pembersihan → Stok Mentah Bersih → Produksi Bumbu → Produksi Pasta → Stok Gudang Matang.</p>
        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="min-w-[900px] w-full text-sm">
                <thead class="bg-red-50 text-red-900 dark:bg-red-950/40 dark:text-red-200">
                    <tr>
                        <th class="px-3 py-2 text-left">Tahap</th>
                        <th class="px-3 py-2 text-right">Stok Awal</th>
                        <th class="px-3 py-2 text-right">Stok Masuk</th>
                        <th class="px-3 py-2 text-right">Stok Keluar</th>
                        <th class="px-3 py-2 text-right">Sisa</th>
                        <th class="px-3 py-2 text-right">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['Stok Mentah Kotor', 29.95, 20, 49.95, 0, 2497500],
                        ['Proses Pembersihan', 49.95, 0, 20.15, 29.80, 2497500],
                        ['Stok Mentah Bersih', 17.10, 14.55, 9.50, 22.15, 1742850],
                        ['Produksi Bumbu', 0, 28.50, 0, 28.50, 1498600],
                        ['Produksi Pasta', 0, 72, 37, 35, 6276240],
                        ['Stok Gudang Matang', 86, 72, 37, 121, 6981182],
                    ] as $row)
                        <tr class="border-t border-gray-100 hover:bg-red-50/40 dark:border-gray-800 dark:hover:bg-red-950/20">
                            <td class="px-3 py-2 font-medium">{{ $row[0] }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row[1]) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row[2]) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row[3]) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::decimal($row[4]) }}</td>
                            <td class="px-3 py-2 text-right">{{ \App\Support\IndoNumber::rupiah($row[5]) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-filament-panels::page>
