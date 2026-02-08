@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.buildings.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.buildings.show-manager',
        'pages.buildings.show-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
