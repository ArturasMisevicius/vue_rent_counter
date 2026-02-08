@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.meter-readings.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.meter-readings.show-manager',
        'pages.meter-readings.show-tenant',
    ]));
@endphp

@includeFirst($viewCandidates)
