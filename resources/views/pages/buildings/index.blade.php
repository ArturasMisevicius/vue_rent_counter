@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.buildings.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.buildings.index-manager',
        'pages.buildings.index-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
