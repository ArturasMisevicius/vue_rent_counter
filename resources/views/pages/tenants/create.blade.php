@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.tenants.create-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.tenants.create-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
