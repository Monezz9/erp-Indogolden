<x-filament-panels::page>
    @php
        $summary = $this->summary();
        $today = $this->todaySummary();
        $month = $this->monthSummary();
        $flow = $this->sevenDayFlow();
        $latestExpenses = $this->latestExpenses();
        $groupedRows = $this->groupedRows();
        $expenseUrl = \App\Filament\Resources\FinanceExpenses\FinanceExpenseResource::getUrl();
    @endphp

    <div class="ig-book">
        <section class="ig-book-hero">
            <div>
                <p class="ig-book-eyebrow">INDOGOLDEN Finance</p>
                <h2>Buku Kas</h2>
                <p>Pembukuan arus kas bisnis kuliner: kas masuk, biaya keluar, posisi saldo, dan kanal pembayaran dalam satu layar.</p>
            </div>

            <div class="ig-book-filters">
                <input
                    type="search"
                    wire:model.live.debounce.400ms="search"
                    class="ig-book-input"
                    placeholder="Cari kategori, deskripsi, cabang..."
                />

                <select
                    wire:model.live="branchId"
                    @disabled($this->isBranchUser())
                    class="ig-book-select"
                >
                    <option value="">Semua cabang</option>
                    @foreach($this->getBranchOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>

                <input
                    type="date"
                    wire:model.live="startDate"
                    class="ig-book-input"
                    aria-label="Tanggal mulai"
                />

                <input
                    type="date"
                    wire:model.live="endDate"
                    class="ig-book-input"
                    aria-label="Tanggal akhir"
                />
            </div>
        </section>

        <section class="ig-book-kpis">
            <article class="ig-book-card ig-book-kpi ig-book-kpi--purple">
                <span>Saldo Saat Ini</span>
                <strong>{{ \App\Support\FinanceBook::rupiah($summary['balance']) }}</strong>
                <small>Akumulasi semua transaksi</small>
            </article>
            <article class="ig-book-card ig-book-kpi ig-book-kpi--green">
                <span>Pemasukan Hari Ini</span>
                <strong>{{ \App\Support\FinanceBook::rupiah($today['income']) }}</strong>
                <small>Debit hari ini</small>
            </article>
            <article class="ig-book-card ig-book-kpi ig-book-kpi--red">
                <span>Pengeluaran Hari Ini</span>
                <strong>{{ \App\Support\FinanceBook::rupiah($today['expense']) }}</strong>
                <small>Kredit hari ini</small>
            </article>
            <article class="ig-book-card ig-book-kpi ig-book-kpi--blue">
                <span>Saldo Hari Ini</span>
                <strong>{{ \App\Support\FinanceBook::rupiah($today['balance']) }}</strong>
                <small>Pemasukan dikurangi pengeluaran</small>
            </article>
        </section>

        <section class="ig-book-cash">
            <article class="ig-book-card">
                <span>Kas</span>
                <strong>{{ \App\Support\FinanceBook::rupiah($summary['cash']) }}</strong>
            </article>
            <article class="ig-book-card">
                <span>Bank</span>
                <strong>{{ \App\Support\FinanceBook::rupiah($summary['bank']) }}</strong>
            </article>
            <article class="ig-book-card">
                <span>QRIS / Aplikasi</span>
                <strong>{{ \App\Support\FinanceBook::rupiah($summary['application']) }}</strong>
            </article>
        </section>

        <section class="ig-book-main">
            <div class="ig-book-left">
                <section class="ig-book-card ig-book-section">
                    <div class="ig-book-section-head">
                        <div>
                            <h3>Pengeluaran Terakhir</h3>
                            <p>Biaya terbaru yang mengurangi saldo.</p>
                        </div>
                        <a href="{{ $expenseUrl }}">Lihat Semua</a>
                    </div>

                    <div class="ig-book-expenses">
                        @forelse($latestExpenses as $expense)
                            <div class="ig-book-expense">
                                <div>
                                    <strong>{{ $expense->notes ?: ($expense->category?->name ?? 'Pengeluaran') }}</strong>
                                    <span>{{ $expense->transaction_date?->format('d M Y') }} | {{ $expense->branch?->name ?? 'Semua cabang' }}</span>
                                </div>
                                <b>{{ \App\Support\FinanceBook::rupiah($expense->amount) }}</b>
                            </div>
                        @empty
                            <div class="ig-book-empty">Belum ada pengeluaran tercatat.</div>
                        @endforelse
                    </div>
                </section>

                <section class="ig-book-card ig-book-section">
                    <div class="ig-book-section-head">
                        <div>
                            <h3>Grafik Arus Kas 7 Hari</h3>
                            <p>Perbandingan pemasukan dan pengeluaran harian.</p>
                        </div>
                    </div>

                    <div class="ig-book-chart">
                        @foreach($flow as $day)
                            <div class="ig-book-chart-row">
                                <span>{{ $day['label'] }}</span>
                                <div>
                                    <i class="ig-book-bar ig-book-bar--green" style="width: {{ max(4, ($day['income'] / $day['max']) * 100) }}%"></i>
                                    <i class="ig-book-bar ig-book-bar--red" style="width: {{ max(4, ($day['expense'] / $day['max']) * 100) }}%"></i>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="ig-book-card ig-book-section">
                    <div class="ig-book-section-head">
                        <div>
                            <h3>Ringkasan Bulan Ini</h3>
                            <p>{{ now()->format('F Y') }}</p>
                        </div>
                    </div>

                    <div class="ig-book-month">
                        <div><span>Pemasukan</span><strong class="green">{{ \App\Support\FinanceBook::rupiah($month['income']) }}</strong></div>
                        <div><span>Pengeluaran</span><strong class="red">{{ \App\Support\FinanceBook::rupiah($month['expense']) }}</strong></div>
                        <div><span>Saldo Bulan Ini</span><strong class="purple">{{ \App\Support\FinanceBook::rupiah($month['balance']) }}</strong></div>
                        <div><span>Jumlah Transaksi</span><strong>{{ $month['count'] }}</strong></div>
                    </div>
                </section>
            </div>

            <section class="ig-book-card ig-book-section ig-book-table-card">
                <div class="ig-book-section-head">
                    <div>
                        <h3>Tabel Buku Kas</h3>
                        <p>Transaksi dikelompokkan per tanggal dengan debit, kredit, saldo berjalan, dan kanal pembayaran.</p>
                    </div>
                </div>

                <div class="ig-book-table-wrap">
                    <table class="ig-book-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Masuk (Debit)</th>
                                <th>Keluar (Kredit)</th>
                                <th>Saldo</th>
                                <th>Kanal pembayaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groupedRows as $date => $rows)
                                <tr class="ig-book-date-row">
                                    <td colspan="7">{{ $date }}</td>
                                </tr>
                                @foreach($rows as $row)
                                    @php
                                        $categoryClass = match ($row['category']) {
                                            'Pendapatan' => 'green',
                                            'Logistik' => 'orange',
                                            'OPEX' => 'blue',
                                            'NOPEX' => 'purple',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $row['date_label'] }}</td>
                                        <td><span class="ig-book-badge ig-book-badge--{{ $categoryClass }}">{{ $row['category'] }}</span></td>
                                        <td>{{ $row['description'] }}</td>
                                        <td class="ig-book-money green">{{ $row['debit'] > 0 ? \App\Support\FinanceBook::rupiah($row['debit']) : '-' }}</td>
                                        <td class="ig-book-money red">{{ $row['credit'] > 0 ? \App\Support\FinanceBook::rupiah($row['credit']) : '-' }}</td>
                                        <td class="ig-book-money strong">{{ \App\Support\FinanceBook::rupiah($row['balance']) }}</td>
                                        <td><span class="ig-book-channel">{{ $row['payment_method'] }}</span></td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7" class="ig-book-empty">Belum ada transaksi arus kas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </div>
</x-filament-panels::page>
