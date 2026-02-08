@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.managers.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.managers.show-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
