@php
    $role = auth()->user()?->role?->value;

    $roleView = in_array($role, ['superadmin', 'admin', 'manager', 'tenant'], true)
        ? 'pages.invoices.drafts-' . $role
        : null;

    $viewCandidates = array_values(array_filter([
        $roleView,
        'pages.invoices.drafts-manager',
    ]));
@endphp

@includeFirst($viewCandidates)
