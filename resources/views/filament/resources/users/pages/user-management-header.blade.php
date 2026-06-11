<header class="ig-users-hero">
    @if ($breadcrumbs)
        <div class="ig-users-hero__breadcrumbs">
            <x-filament::breadcrumbs :breadcrumbs="$breadcrumbs" />
        </div>
    @endif

    <div class="ig-users-hero__body">
        <div class="ig-users-hero__copy">
            <div class="ig-users-hero__title-row">
                <span class="ig-users-hero__icon">
                    <x-filament::icon :icon="$icon ?? 'heroicon-o-users'" />
                </span>
                <div>
                    <h1>{{ $title }}</h1>
                    <p>{{ $subtitle }}</p>
                </div>
            </div>
        </div>

        @if ($actions)
            <div class="ig-users-hero__actions">
                <x-filament::actions
                    :actions="$actions"
                    :alignment="$actionsAlignment"
                />
            </div>
        @endif
    </div>
</header>
