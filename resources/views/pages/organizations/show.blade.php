@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.organizations.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.organizations.show-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
