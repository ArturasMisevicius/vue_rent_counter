@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.reports.revenue-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.reports.revenue-manager',
    ]));
@endphp

@includeFirst($viewCandidates)
