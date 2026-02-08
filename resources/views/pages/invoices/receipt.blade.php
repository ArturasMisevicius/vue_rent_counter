@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.invoices.receipt-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.invoices.receipt-tenant',
    ]));
@endphp

@includeFirst($viewCandidates)
