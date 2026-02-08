@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.users.edit-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.users.edit-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
