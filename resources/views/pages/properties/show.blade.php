@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.properties.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.properties.show-admin',
        'pages.properties.show-manager',
        'pages.properties.show-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
