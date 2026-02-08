@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.users.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.users.show-admin',
        'pages.users.show-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
