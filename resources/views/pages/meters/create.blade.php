@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.meters.create-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.meters.create-manager',
    ]));
@endphp

@includeFirst($viewCandidates)
