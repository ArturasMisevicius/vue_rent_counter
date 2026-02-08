@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.tariffs.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.tariffs.index-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
