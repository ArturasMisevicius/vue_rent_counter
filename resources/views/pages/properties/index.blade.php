@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.properties.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.properties.index-manager',
        'pages.properties.index-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
