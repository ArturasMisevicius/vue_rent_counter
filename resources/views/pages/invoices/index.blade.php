@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.invoices.index-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.invoices.index-manager',
        'pages.invoices.index-tenant',
    ]));
@endphp

@includeFirst($viewCandidates)
