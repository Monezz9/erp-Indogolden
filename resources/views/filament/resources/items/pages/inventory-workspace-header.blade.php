<header class="ig-inventory-hero">
    @if ($breadcrumbs)
        <div class="ig-inventory-hero__breadcrumbs">
            <x-filament::breadcrumbs :breadcrumbs="$breadcrumbs" />
        </div>
    @endif

    <div class="ig-inventory-hero__body">
        <div class="ig-inventory-hero__copy">
            <div class="ig-inventory-hero__title-row">
                <span class="ig-inventory-hero__icon">
                    <x-filament::icon icon="heroicon-o-cube" />
                </span>
                <div>
                    <h1>Manajemen Barang</h1>
                    <p>Kelola seluruh master barang, kategori, dan stok untuk mendukung operasional INDOGOLDEN.</p>
                </div>
            </div>
        </div>

        @if ($actions)
            <div class="ig-inventory-hero__actions">
                <x-filament::actions
                    :actions="$actions"
                    :alignment="$actionsAlignment"
                />
            </div>
        @endif
    </div>
</header>
