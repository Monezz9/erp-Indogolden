<x-filament-widgets::widget class="ig-inventory-summary-widget">
    <div class="ig-inventory-summary-grid">
        @foreach ($this->inventorySummaryCards() as $card)
            <article class="ig-inventory-summary-card ig-inventory-summary-card--{{ $card['tone'] }}">
                <div class="ig-inventory-summary-card__icon">
                    <x-filament::icon :icon="$card['icon']" />
                </div>

                <div class="ig-inventory-summary-card__content">
                    <p class="ig-inventory-summary-card__label">
                        {{ $card['label'] }}
                    </p>

                    <p class="ig-inventory-summary-card__value">
                        {{ $card['value'] }}
                    </p>

                    <p class="ig-inventory-summary-card__description">
                        {{ $card['description'] }}
                    </p>
                </div>
            </article>
        @endforeach
    </div>
</x-filament-widgets::widget>
