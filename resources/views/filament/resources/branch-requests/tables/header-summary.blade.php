<div class="ig-branch-request-pillbar">
    @foreach ($stats as $stat)
        <div class="ig-branch-request-pill ig-branch-request-pill--{{ $stat['tone'] }}">
            <x-filament::icon :icon="$stat['icon']" />
            <span>{{ $stat['label'] }}:</span>
            <strong>{{ $stat['value'] }}</strong>
        </div>
    @endforeach
</div>
