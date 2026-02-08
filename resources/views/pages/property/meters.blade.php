@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.property.meters-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.property.meters-tenant',
    ]));
@endphp

@includeFirst($viewCandidates)
