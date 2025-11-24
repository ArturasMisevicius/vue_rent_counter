@php
    $hideBreadcrumbs = auth()->user()?->role?->value === 'manager';
@endphp

@if(!$hideBreadcrumbs)
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center gap-1 bg-white/80 border border-slate-200 rounded-full px-3 py-2 shadow-sm shadow-slate-200/60 backdrop-blur">
        {{ $slot }}
    </ol>
</nav>
@endif
