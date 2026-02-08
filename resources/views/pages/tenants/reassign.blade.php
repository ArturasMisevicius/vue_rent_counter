@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.tenants.reassign-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.tenants.reassign-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
