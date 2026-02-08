@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.users.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.users.index-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
