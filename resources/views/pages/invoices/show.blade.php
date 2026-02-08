@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.invoices.show-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.invoices.show-manager',
        'pages.invoices.show-tenant',
    ]));
@endphp

@includeFirst($viewCandidates)
