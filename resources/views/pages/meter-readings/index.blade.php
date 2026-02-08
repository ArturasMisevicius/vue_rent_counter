@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.meter-readings.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.meter-readings.index-manager',
        'pages.meter-readings.index-tenant',
    ]));
@endphp

@includeFirst($viewCandidates)
