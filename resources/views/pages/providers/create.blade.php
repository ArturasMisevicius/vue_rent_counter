@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.providers.create-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.providers.create-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
