@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.meters.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.meters.show-manager',
        'pages.meters.show-tenant',
    ]));
@endphp

@includeFirst($viewCandidates)
