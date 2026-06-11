<x-filament-panels::page>
    @php
        $selectedItem = $this->selectedItem();
        $canSave = $this->canSaveDraft();
    @endphp

    <div class="ig-procurement">
        <header class="ig-procurement-hero">
            <div class="ig-procurement-hero__title">
                <span class="ig-procurement-hero__icon">
                    <x-filament::icon icon="heroicon-o-clipboard-document-list" />
                </span>
                <div>
                    <h1>Ruang Kerja Pengadaan</h1>
                    <p>Buat permintaan pembelian barang untuk kebutuhan gudang dan operasional.</p>
                </div>
            </div>

            <div class="ig-procurement-steps">
                <span>1. Informasi PO</span>
                <span>2. Tambah Barang</span>
                <span>3. Review Draft</span>
                <span>4. Simpan Pengadaan</span>
            </div>
        </header>

        <div class="ig-procurement-layout">
            <main class="ig-procurement-main">
                <section class="ig-procurement-card">
                    <div class="ig-procurement-card__head">
                        <div>
                            <h2>Informasi Pengadaan</h2>
                            <p>Pilih supplier dan tanggal pengadaan sebelum menambahkan barang.</p>
                        </div>
                    </div>

                    <div class="ig-procurement-info-grid">
                        <label class="ig-procurement-field">
                            <span>No Transaksi</span>
                            <input type="text" wire:model="transactionNumber" readonly />
                            <small>Nomor dibuat otomatis oleh sistem.</small>
                        </label>

                        <label class="ig-procurement-field">
                            <span>Tanggal</span>
                            <input type="date" wire:model.live="orderDate" />
                        </label>

                        <label class="ig-procurement-field">
                            <span>Supplier</span>
                            <select wire:model.live="supplierId">
                                <option value="">Pilih supplier pengadaan</option>
                                @foreach($this->supplierOptions() as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </section>

                <section
                    class="ig-procurement-card"
                    x-data="{
                        purchaseQty: @entangle('purchaseQty'),
                        conversionQty: @entangle('conversionQty'),
                        unitCost: @entangle('unitCost'),
                        baseQty() {
                            return Number(this.purchaseQty || 0) * Number(this.conversionQty || 0);
                        },
                        baseUnitCost() {
                            const qty = this.baseQty();
                            return qty > 0 ? Number(this.unitCost || 0) / qty : 0;
                        },
                        decimal(value) {
                            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(value || 0));
                        },
                        rupiah(value) {
                            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));
                        },
                    }"
                >
                    <div class="ig-procurement-card__head">
                        <div>
                            <h2>Tambah Barang</h2>
                            <p>Cari item, isi qty pembelian, lalu cek konversi stok sebelum masuk draft.</p>
                        </div>
                        <span class="ig-procurement-card__badge">Input compact</span>
                    </div>

                    <div class="ig-procurement-item-grid">
                        <label class="ig-procurement-field ig-procurement-field--search">
                            <span>Cari barang / kode item</span>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="itemSearch"
                                wire:focus="openItemSearchResults"
                                placeholder="Ketik SKU atau nama barang"
                                autocomplete="off"
                            />
                            <small>Hanya barang kategori RM dan SRM yang dapat diajukan dalam pengadaan.</small>

                            @if(trim((string) $itemSearch) !== '' && ! $itemId)
                                <div class="ig-procurement-search-results">
                                    @forelse($this->itemSearchResults() as $result)
                                        <button type="button" wire:click="selectItem({{ $result['id'] }})">
                                            <strong>{{ $result['label'] }}</strong>
                                            <span>{{ $result['category'] }}</span>
                                        </button>
                                    @empty
                                        <div class="ig-procurement-search-results__empty">Tidak ada barang RM/SRM yang tersedia untuk pengadaan.</div>
                                    @endforelse
                                </div>
                            @endif
                        </label>

                        <label class="ig-procurement-field">
                            <span>Kategori otomatis</span>
                            <input type="text" value="{{ $this->selectedItemCategoryLabel() }}" readonly />
                        </label>

                        <label class="ig-procurement-field">
                            <span>Keterangan otomatis</span>
                            <input type="text" value="{{ $selectedItem?->name ?? '-' }}" readonly />
                        </label>
                    </div>

                    <div class="ig-procurement-qty-grid">
                        <label class="ig-procurement-field">
                            <span>Qty Beli</span>
                            <input type="number" step="any" min="0" x-model.number="purchaseQty" />
                        </label>

                        <label class="ig-procurement-field">
                            <span>Satuan Beli</span>
                            <select wire:model.live="purchaseUnitId">
                                <option value="">Pilih satuan beli</option>
                                @foreach($this->unitOptions() as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="ig-procurement-field">
                            <span>Isi per Satuan</span>
                            <input type="number" step="any" min="0" x-model.number="conversionQty" />
                        </label>

                        <label class="ig-procurement-field">
                            <span>Satuan Stok</span>
                            <select wire:model.live="unitId">
                                <option value="">Pilih satuan stok</option>
                                @foreach($this->unitOptions() as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="ig-procurement-price-grid">
                        <label class="ig-procurement-field">
                            <span>Estimasi Total Harga</span>
                            <input type="number" step="any" min="0" x-model.number="unitCost" />
                        </label>

                        <div class="ig-procurement-conversion-preview">
                            <span>Preview konversi</span>
                            <strong>
                                <span x-text="decimal(purchaseQty)">{{ \App\Support\IndoNumber::decimal($purchaseQty) }}</span>
                                {{ $this->selectedPurchaseUnitCode() }}
                                x
                                <span x-text="decimal(conversionQty)">{{ \App\Support\IndoNumber::decimal($conversionQty) }}</span>
                                {{ $this->selectedStockUnitCode() }}
                                =
                                <span x-text="decimal(baseQty())">{{ \App\Support\IndoNumber::decimal($this->baseQty()) }}</span>
                                {{ $this->selectedStockUnitCode() }} masuk stok
                            </strong>
                            <small>
                                Estimasi HPP:
                                <span x-text="rupiah(baseUnitCost())">{{ \App\Support\IndoNumber::rupiah($this->baseUnitCost()) }}</span>
                                / {{ $this->selectedStockUnitCode() }}
                            </small>
                        </div>
                    </div>

                    <div class="ig-procurement-add-row">
                        <button type="button" wire:click="addItemToCart" class="ig-procurement-add-button">
                            <x-filament::icon icon="heroicon-o-plus" />
                            Tambahkan ke Draft
                        </button>
                    </div>
                </section>

                <section class="ig-procurement-card">
                    <div class="ig-procurement-card__head">
                        <div>
                            <h2>Draft Item Pengadaan</h2>
                            <p>Review item sebelum disimpan menjadi draft pengadaan.</p>
                        </div>
                        @if($cart !== [])
                            <button type="button" wire:click="clearCart" class="ig-procurement-clear-button">Kosongkan</button>
                        @endif
                    </div>

                    <div class="ig-procurement-draft-list">
                        @forelse($cart as $index => $line)
                            @php
                                $isValidProcurementItem = $this->cartLineIsAllowed((int) $line['item_id']);
                            @endphp
                            <article class="ig-procurement-draft-item">
                                <div class="ig-procurement-draft-item__main">
                                    <h3>{{ $line['item_name'] }}</h3>
                                    <p>{{ $line['item_label'] }} / {{ $line['item_kind'] ?: '-' }}</p>
                                    @unless($isValidProcurementItem)
                                        <span class="ig-procurement-invalid-badge">Kategori Tidak Valid</span>
                                    @endunless
                                </div>

                                <div class="ig-procurement-draft-item__metric">
                                    <span>Qty Beli</span>
                                    <strong>{{ \App\Support\IndoNumber::decimal($line['purchase_qty']) }} {{ \Illuminate\Support\Str::before($line['purchase_unit_label'], ' - ') }}</strong>
                                </div>

                                <div class="ig-procurement-draft-item__metric">
                                    <span>Konversi</span>
                                    <strong>{{ \App\Support\IndoNumber::decimal($line['purchase_qty']) }} x {{ \App\Support\IndoNumber::decimal($line['conversion_qty']) }}</strong>
                                </div>

                                <div class="ig-procurement-draft-item__metric">
                                    <span>Estimasi Stok</span>
                                    <strong>{{ \App\Support\IndoNumber::decimal($line['ordered_qty']) }} {{ \Illuminate\Support\Str::before($line['unit_label'], ' - ') }}</strong>
                                </div>

                                <div class="ig-procurement-draft-item__actions">
                                    <button type="button" wire:click="editCartItem({{ $index }})">Edit</button>
                                    <button type="button" wire:click="removeCartItem({{ $index }})" class="is-danger">Hapus</button>
                                </div>
                            </article>
                        @empty
                            <div class="ig-procurement-empty">
                                <x-filament::icon icon="heroicon-o-inbox-stack" />
                                <h3>Belum ada barang di draft PO</h3>
                                <p>Tambahkan barang dari form di atas untuk mulai membuat pengadaan.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </main>

            <aside class="ig-procurement-summary">
                <div class="ig-procurement-summary-card">
                    <span class="ig-procurement-summary-card__badge">Draft PO</span>
                    <h2>Ringkasan Pengadaan</h2>

                    <dl>
                        <div>
                            <dt>No Transaksi</dt>
                            <dd>{{ $transactionNumber }}</dd>
                        </div>
                        <div>
                            <dt>Supplier</dt>
                            <dd>{{ $this->supplierName() }}</dd>
                        </div>
                        <div>
                            <dt>Tanggal</dt>
                            <dd>{{ $orderDate ? \Carbon\Carbon::parse($orderDate)->format('d M Y') : '-' }}</dd>
                        </div>
                        <div>
                            <dt>Total Item</dt>
                            <dd>{{ count($cart) }} item</dd>
                        </div>
                        <div>
                            <dt>Total Qty Beli</dt>
                            <dd>{{ \App\Support\IndoNumber::decimal($this->cartTotalPurchaseQty()) }}</dd>
                        </div>
                        <div>
                            <dt>Estimasi Masuk Stok</dt>
                            <dd>{{ \App\Support\IndoNumber::decimal($this->cartTotalOrderedQty()) }}</dd>
                        </div>
                        <div>
                            <dt>Total Estimasi</dt>
                            <dd>{{ \App\Support\IndoNumber::rupiah($this->cartTotal()) }}</dd>
                        </div>
                        <div>
                            <dt>Status</dt>
                            <dd>Draft</dd>
                        </div>
                    </dl>

                    <label class="ig-procurement-field">
                        <span>Catatan</span>
                        <textarea
                            wire:model.live="notes"
                            rows="4"
                            placeholder="Contoh: Pembelian bahan baku mingguan / restock cabang / kebutuhan produksi."
                        ></textarea>
                    </label>

                    <div class="ig-procurement-summary-card__info">
                        Setelah disimpan, draft pengadaan akan masuk ke alur review finance.
                    </div>

                    @unless($canSave)
                        <div class="ig-procurement-summary-card__warning">
                            Tambahkan minimal 1 barang ke draft PO.
                        </div>
                    @endunless

                    <button
                        type="button"
                        wire:click="createPurchaseOrder"
                        @disabled(! $canSave)
                        class="ig-procurement-save-button"
                    >
                        Simpan Pengadaan
                    </button>

                    <a href="{{ \App\Filament\Pages\ProcurementHistory::getUrl() }}" class="ig-procurement-back-button">
                        Batal / Kembali
                    </a>
                </div>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
