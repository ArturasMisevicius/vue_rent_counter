@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.audit.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.audit.index-admin',
    ]));
@endphp

@includeFirst($viewCandidates)
