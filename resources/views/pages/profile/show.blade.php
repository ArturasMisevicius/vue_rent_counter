@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.profile.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.profile.show-admin',
        'pages.profile.show-manager',
        'pages.profile.show-superadmin',
        'pages.profile.show-tenant',
    ]));
@endphp

@includeFirst($viewCandidates)
