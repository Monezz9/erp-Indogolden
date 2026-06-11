@php
    $initials = collect(explode(' ', trim((string) $record->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->implode('') ?: 'U';
@endphp

<div class="ig-user-profile-cell">
    <div class="ig-user-avatar">{{ $initials }}</div>
    <div class="ig-user-profile-cell__copy">
        <div class="ig-user-profile-cell__name">{{ $record->name }}</div>
        <div class="ig-user-profile-cell__meta">@{{ $record->username }}</div>
        <div class="ig-user-profile-cell__email">{{ $record->email }}</div>
    </div>
</div>
