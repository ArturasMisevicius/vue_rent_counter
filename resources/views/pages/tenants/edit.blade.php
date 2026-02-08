@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.tenants.edit-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.tenants.edit-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
