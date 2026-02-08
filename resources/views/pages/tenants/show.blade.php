@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.tenants.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.tenants.show-admin',
        'pages.tenants.show-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
