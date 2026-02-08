@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.providers.edit-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.providers.edit-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
