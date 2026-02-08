@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.tenants.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.tenants.index-admin',
        'pages.tenants.index-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
