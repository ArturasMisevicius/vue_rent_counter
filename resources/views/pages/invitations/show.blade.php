@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.invitations.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.invitations.show-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
