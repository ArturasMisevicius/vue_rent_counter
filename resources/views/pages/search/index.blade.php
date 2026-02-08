@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.search.' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.search.superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
