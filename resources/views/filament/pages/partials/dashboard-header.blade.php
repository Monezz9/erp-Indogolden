<div class="ig-dashboard-header">
    <div class="ig-dashboard-header__copy">
        <p class="ig-dashboard-header__eyebrow">INDOGOLDEN ERP</p>
        <h1>Ringkasan Operasional</h1>
        <p>Pantau performa operasional gudang, transfer, produksi, dan stok secara real-time.</p>
    </div>

    <div class="ig-dashboard-header__actions">
        <label for="ig-dashboard-date">Tanggal pantau</label>
        <div class="ig-dashboard-date">
            <x-filament::icon icon="heroicon-o-calendar-date-range" class="ig-dashboard-date__icon" />
            <input id="ig-dashboard-date" type="date" value="{{ now()->toDateString() }}">
        </div>
    </div>
</div>
