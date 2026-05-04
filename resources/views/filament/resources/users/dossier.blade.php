<div class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">{{ __('superadmin.users.dossier.title') }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ __('superadmin.users.dossier.description') }}
                </p>
            </div>

            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('superadmin.users.dossier.total_sections') }}</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ count($sections) }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($summary as $item)
                <article class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $item['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ $item['value'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    @foreach ($sections as $section)
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950">{{ $section['title'] }}</h2>
                    @if ($section['data'] === null)
                        <p class="mt-1 text-sm text-slate-500">{{ $section['empty'] }}</p>
                    @endif
                </div>

                @if ($section['count'] !== null)
                    <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-700">
                        {{ $section['count'] }}
                    </span>
                @endif
            </div>

            <div class="mt-6">
                @if ($section['data'] === null)
                    <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-4 text-sm text-slate-500">
                        {{ $section['empty'] }}
                    </div>
                @else
                    @include('filament.resources.users.partials.dossier-tree', ['data' => $section['data']])
                @endif
            </div>
        </section>
    @endforeach
</div>
