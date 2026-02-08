@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.subscriptions.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.subscriptions.show-superadmin',
    ]));
@endphp

@includeFirst($viewCandidates)
