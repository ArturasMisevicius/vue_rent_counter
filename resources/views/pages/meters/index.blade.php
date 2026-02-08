@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.meters.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.meters.index-manager',
        'pages.meters.index-tenant',
    ]));
@endphp

@includeFirst($viewCandidates)
