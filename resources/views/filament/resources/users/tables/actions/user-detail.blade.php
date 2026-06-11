@php
    use App\Support\UserAccessProfile;

    $roles = UserAccessProfile::roleNamesForUser($record);
    $permissions = UserAccessProfile::permissions($roles);
    $initials = collect(explode(' ', trim((string) $record->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
        ->implode('') ?: 'U';
@endphp

<div class="ig-user-detail">
    <div class="ig-user-detail__header">
        <div class="ig-user-avatar ig-user-avatar--lg">{{ $initials }}</div>
        <div>
            <div class="ig-user-detail__name">{{ $record->name }}</div>
            <div class="ig-user-detail__meta">@{{ $record->username }} | {{ $record->email }}</div>
        </div>
    </div>

    <div class="ig-user-detail__grid">
        <div>
            <span>Cabang / Area</span>
            <strong>{{ $record->branch?->name ?? 'Head Office' }}</strong>
        </div>
        <div>
            <span>Status</span>
            <strong>{{ $record->is_active ? 'Aktif' : 'Nonaktif' }}</strong>
        </div>
        <div>
            <span>Dibuat</span>
            <strong>{{ $record->created_at?->format('d M Y H:i') ?? '-' }}</strong>
        </div>
        <div>
            <span>Terakhir Update</span>
            <strong>{{ $record->updated_at?->format('d M Y H:i') ?? '-' }}</strong>
        </div>
    </div>

    <div class="ig-user-detail__roles">
        @forelse ($roles as $role)
            <span class="ig-user-role-badge ig-user-role-badge--{{ UserAccessProfile::roleTone($role) }}">
                {{ UserAccessProfile::roleLabel($role) }}
            </span>
        @empty
            <span class="ig-user-role-badge ig-user-role-badge--gray">Belum ada role</span>
        @endforelse
    </div>

    <div class="ig-user-access-checklist">
        @foreach ($permissions as $label => $allowed)
            <div class="{{ $allowed ? 'is-allowed' : 'is-muted' }}">
                <x-filament::icon :icon="$allowed ? 'heroicon-o-check-circle' : 'heroicon-o-minus-circle'" />
                <span>{{ $label }}</span>
            </div>
        @endforeach
    </div>
</div>
