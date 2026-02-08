@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.dashboard.' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.dashboard.admin',
        'pages.dashboard.manager',
        'pages.dashboard.superadmin',
        'pages.dashboard.tenant',
    ]));
@endphp

@includeFirst($viewCandidates)
