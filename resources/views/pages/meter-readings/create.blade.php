@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.meter-readings.create-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.meter-readings.create-manager',
    ]));
@endphp

@includeFirst($viewCandidates)
