@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.properties.create-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.properties.create-manager',
    ]));
@endphp

@includeFirst($viewCandidates)
