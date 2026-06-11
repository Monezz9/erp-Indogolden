<x-filament-widgets::widget class="ig-users-overview-widget">
    <div class="ig-users-kpi-grid">
        @foreach ($this->cards() as $card)
            <article class="ig-users-kpi-card ig-users-kpi-card--{{ $card['tone'] }}">
                <div class="ig-users-kpi-card__icon">
                    <x-filament::icon :icon="$card['icon']" />
                </div>

                <div>
                    <p class="ig-users-kpi-card__label">{{ $card['label'] }}</p>
                    <p class="ig-users-kpi-card__value">{{ $card['value'] }}</p>
                    <p class="ig-users-kpi-card__description">{{ $card['description'] }}</p>
                </div>
            </article>
        @endforeach
    </div>
</x-filament-widgets::widget>
