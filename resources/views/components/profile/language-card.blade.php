@props([
    'title',
    'description' => null,
    'languages' => collect(),
])

@if($languages->isNotEmpty())
    <x-card :title="$title" class="divide-y divide-slate-100">
        <div class="space-y-4">
            @if($description)
                <p class="text-sm text-slate-600">{{ $description }}</p>
            @endif

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($languages as $language)
                    <a
                        href="{{ route('language.switch', $language->code) }}"
                        @class([
                            'flex items-center justify-between gap-3 rounded-xl border px-3 py-2 text-sm font-medium transition',
                            'border-indigo-200 bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' => $language->code === app()->getLocale(),
                            'border-slate-200 bg-white text-slate-700 hover:border-indigo-200 hover:bg-indigo-50/40' => $language->code !== app()->getLocale(),
                        ])
                    >
                        <span>{{ $language->native_name ?? $language->name }}</span>
                        @if($language->code === app()->getLocale())
                            <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </x-card>
@endif
