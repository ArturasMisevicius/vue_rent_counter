@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.tariffs.create-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.tariffs.create-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
