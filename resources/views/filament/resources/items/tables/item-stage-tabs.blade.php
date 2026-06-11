@php
    $tabs = $page->getCachedTabs();
    $activeTab = $page->activeTab ?? $page->getDefaultActiveTab();
@endphp

<div class="ig-items-table-tabs" aria-label="Filter kategori barang">
    @foreach ($tabs as $tabKey => $tab)
        @php
            $isActive = (string) $activeTab === (string) $tabKey;
        @endphp

        <button
            type="button"
            wire:key="items-table-tab-{{ $tabKey }}"
            wire:click="$set('activeTab', @js((string) $tabKey))"
            @class([
                'ig-items-table-tabs__item',
                'ig-items-table-tabs__item--active' => $isActive,
            ])
            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
        >
            {{ $tab->getLabel() }}
        </button>
    @endforeach
</div>
