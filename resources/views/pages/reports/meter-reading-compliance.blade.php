@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.reports.meter-reading-compliance-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.reports.meter-reading-compliance-manager',
    ]));
@endphp

@includeFirst($viewCandidates)
