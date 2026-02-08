@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.tariffs.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.tariffs.show-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
