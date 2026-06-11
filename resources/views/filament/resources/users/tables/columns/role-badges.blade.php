@php
    use App\Support\UserAccessProfile;
@endphp

<div class="ig-user-role-list">
    @forelse ($record->roles as $role)
        <span class="ig-user-role-badge ig-user-role-badge--{{ UserAccessProfile::roleTone($role->name) }}">
            {{ UserAccessProfile::roleLabel($role->name) }}
        </span>
    @empty
        <span class="ig-user-role-badge ig-user-role-badge--gray">Belum ada role</span>
    @endforelse
</div>
