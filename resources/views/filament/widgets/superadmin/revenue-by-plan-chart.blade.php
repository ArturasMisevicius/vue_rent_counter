<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h3 class="text-base font-semibold text-slate-950">{{ __('dashboard.platform_sections.revenue_by_plan') }}</h3>
            <p class="text-sm text-slate-500">{{ __('dashboard.platform_sections.revenue_by_plan_description') }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-3 md:grid-cols-3">
        @foreach ($totals as $total)
            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                <p class="text-sm font-semibold text-slate-900">{{ $total['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ $total['amount'] }}</p>
            </article>
        @endforeach
    </div>
</div>
