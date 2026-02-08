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
                        class="ds-language-option {{ $language->code === app()->getLocale() ? 'ds-language-option--active' : 'ds-language-option--inactive' }}"
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
