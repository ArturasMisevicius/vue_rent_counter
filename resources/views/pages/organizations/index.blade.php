@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.organizations.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.organizations.index-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
