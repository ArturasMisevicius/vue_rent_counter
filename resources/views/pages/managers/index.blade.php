@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.managers.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.managers.index-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
