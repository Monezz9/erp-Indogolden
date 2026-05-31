<x-filament-panels::page>
    @php
        $rows = $this->getReportRows();
        $totalOpening = $rows->sum('opening_qty');
        $totalIncoming = $rows->sum('incoming_qty');
        $totalOutgoing = $rows->sum('outgoing_qty');
        $totalClosing = $rows->sum('closing_qty');
        $totalValue = $rows->sum('closing_value');
    @endphp

    <style>
        .bdsr-stack { display: grid; gap: 1rem; }
        .bdsr-hero {
            background: linear-gradient(120deg, #b91c1c 0%, #dc2626 55%, #fb7185 100%);
            color: #fff;
            border-radius: 14px;
            padding: 1rem 1.25rem;
            box-shadow: 0 8px 24px rgba(185, 28, 28, 0.18);
        }
        .bdsr-hero-title { margin: 0; font-size: 1.05rem; font-weight: 700; }
        .bdsr-hero-sub { margin: .35rem 0 0; font-size: .92rem; opacity: .95; }
        .bdsr-grid-2 { display: grid; gap: .75rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .bdsr-grid-5 { display: grid; gap: .75rem; grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .bdsr-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: .875rem 1rem;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
        }
        .bdsr-label { display: block; font-size: .72rem; font-weight: 700; color: #6b7280; letter-spacing: .03em; text-transform: uppercase; }
        .bdsr-input, .bdsr-select {
            width: 100%;
            margin-top: .45rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: .5rem .65rem;
            font-size: .92rem;
            background: #fff;
        }
        .bdsr-value { margin-top: .45rem; font-size: 1.15rem; font-weight: 700; color: #111827; }
        .bdsr-value-green { color: #047857; }
        .bdsr-value-red { color: #be123c; }
        .bdsr-value-deep-red { color: #b91c1c; }

        @media (max-width: 1200px) {
            .bdsr-grid-5 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 768px) {
            .bdsr-grid-2,
            .bdsr-grid-5 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        }
    </style>

    <div class="bdsr-stack">
        <div class="bdsr-hero">
            <h2 class="bdsr-hero-title">Laporan Stok Harian Cabang</h2>
            <p class="bdsr-hero-sub">Carry-over harian: stok awal + masuk - keluar = sisa.</p>
        </div>

        <div class="bdsr-grid-2">
            <label class="bdsr-card">
                <span class="bdsr-label">Tanggal Laporan</span>
                <input
                    type="date"
                    wire:model.live="reportDate"
                    class="bdsr-input"
                />
            </label>

            <label class="bdsr-card">
                <span class="bdsr-label">Cabang</span>
                <select
                    wire:model.live="branchId"
                    @disabled($this->isBranchUser())
                    class="bdsr-select"
                >
                    @foreach($this->getBranchOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="bdsr-grid-5">
            <div class="bdsr-card">
                <div class="bdsr-label">Total Stok Awal</div>
                <div class="bdsr-value">{{ \App\Support\IndoNumber::decimal($totalOpening) }}</div>
            </div>
            <div class="bdsr-card">
                <div class="bdsr-label">Total Masuk</div>
                <div class="bdsr-value bdsr-value-green">{{ \App\Support\IndoNumber::decimal($totalIncoming) }}</div>
            </div>
            <div class="bdsr-card">
                <div class="bdsr-label">Total Keluar</div>
                <div class="bdsr-value bdsr-value-red">{{ \App\Support\IndoNumber::decimal($totalOutgoing) }}</div>
            </div>
            <div class="bdsr-card">
                <div class="bdsr-label">Total Sisa</div>
                <div class="bdsr-value">{{ \App\Support\IndoNumber::decimal($totalClosing) }}</div>
            </div>
            <div class="bdsr-card">
                <div class="bdsr-label">Nilai Stok Akhir</div>
                <div class="bdsr-value bdsr-value-deep-red">{{ \App\Support\IndoNumber::rupiah($totalValue) }}</div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-2 shadow-sm">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
