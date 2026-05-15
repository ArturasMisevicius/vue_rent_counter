@php
    $isList = is_array($data) && array_is_list($data);
    $isScalarList = $isList && collect($data)->every(fn ($item) => ! is_array($item));
@endphp

@if (! is_array($data))
    <div class="text-sm text-slate-900">{{ filled($data) ? $data : '—' }}</div>
@elseif ($data === [])
    <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-4 text-sm text-slate-500">
        {{ __('superadmin.users.dossier.no_values_recorded') }}
    </div>
@elseif ($isScalarList)
    <ul class="space-y-2">
        @foreach ($data as $item)
            <li class="rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm text-slate-900">
                {{ filled($item) ? $item : '—' }}
            </li>
        @endforeach
    </ul>
@elseif ($isList)
    <div class="space-y-4">
        @foreach ($data as $index => $item)
            <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                        {{ __('superadmin.users.dossier.record_number', ['number' => $index + 1]) }}
                    </h3>
                </div>

                @include('filament.resources.users.partials.dossier-tree', ['data' => $item])
            </div>
        @endforeach
    </div>
@else
    <dl class="grid gap-4 sm:grid-cols-2">
        @foreach ($data as $key => $value)
            <div class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-4">
                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    @php($fieldTranslationKey = 'superadmin.users.dossier.fields.'.\App\Filament\Support\Localization\LocalizedCodeLabel::segment((string) $key))
                    {{ trans()->has($fieldTranslationKey) ? __($fieldTranslationKey) : \Illuminate\Support\Str::headline((string) $key) }}
                </dt>
                <dd class="mt-3 text-sm text-slate-900">
                    @if (is_array($value))
                        @include('filament.resources.users.partials.dossier-tree', ['data' => $value])
                    @else
                        {{ filled($value) ? $value : '—' }}
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
@endif
