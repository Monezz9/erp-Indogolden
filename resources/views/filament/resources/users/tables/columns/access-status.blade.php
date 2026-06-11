<span class="ig-user-status-badge {{ $record->is_active ? 'ig-user-status-badge--active' : 'ig-user-status-badge--inactive' }}">
    <x-filament::icon :icon="$record->is_active ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol'" />
    {{ $record->is_active ? 'Aktif' : 'Nonaktif' }}
</span>
