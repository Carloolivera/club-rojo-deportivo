@php
    $user = filament()->auth()->user();
    $roleLabelMap = ['admin' => 'Administrador', 'manager' => 'Gestor', 'authority' => 'Autoridad'];
    $role = $user?->roles->first()?->name ?? '';
    $roleLabel = $roleLabelMap[$role] ?? ucfirst($role ?: 'Admin');
    $initials = collect(explode(' ', $user?->name ?? 'A'))
        ->map(fn ($w) => strtoupper($w[0] ?? ''))
        ->take(2)
        ->join('');
@endphp

<div class="club-sidebar-user">
    <div class="club-sidebar-user-info">
        <div class="club-sidebar-user-avatar">{{ $initials }}</div>
        <div class="club-sidebar-user-text">
            <span class="club-sidebar-user-name">{{ $user?->name }}</span>
            <span class="club-sidebar-user-role">{{ $roleLabel }}</span>
        </div>
    </div>

    <form method="POST" action="{{ filament()->getLogoutUrl() }}">
        @csrf
        <button type="submit" class="club-sidebar-logout-btn" title="Cerrar sesión">
            <svg xmlns="http://www.w3.org/2000/svg" class="club-sidebar-logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </button>
    </form>
</div>
