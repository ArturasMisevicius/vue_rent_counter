@props([
    'currentLocale' => app()->getLocale(),
    'availableLocales' => \App\Support\Localization::availableLocales(),
])

<div class="relative inline-block text-left" x-data="{ open: false }" @click.away="open = false">
    <div>
        <button type="button"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                @click="open = !open"
                :aria-expanded="open"
                aria-haspopup="true"
                aria-label="{{ __('common.language_switcher_label') }}">
            @if(collect($availableLocales)->firstWhere('code', $currentLocale))
                <span class="mr-2">{{ __(collect($availableLocales)->firstWhere('code', $currentLocale)['label']) }}</span>
                <span class="text-xs text-gray-500">({{ collect($availableLocales)->firstWhere('code', $currentLocale)['abbreviation'] }})</span>
            @else
                <span class="mr-2">{{ __('common.language') }}</span>
            @endif

            <svg class="-mr-1 ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>

            <span class="sr-only">
                {{ __('common.current_language') }}:
                {{ collect($availableLocales)->firstWhere('code', $currentLocale) ? __(collect($availableLocales)->firstWhere('code', $currentLocale)['label']) : __('common.language') }}
            </span>
        </button>
    </div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform scale-95 opacity-0"
         x-transition:enter-end="transform scale-100 opacity-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform scale-100 opacity-100"
         x-transition:leave-end="transform scale-95 opacity-0"
         class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5"
         role="menu"
         aria-orientation="vertical"
         aria-labelledby="language-menu">
        <div class="py-1" role="none">
            @foreach($availableLocales as $locale)
                <a href="{{ route('language.switch', $locale['code']) }}"
                   class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:bg-gray-100 focus:text-gray-900 focus:outline-none"
                   role="menuitem"
                   @click="$dispatch('language-changed', { locale: '{{ $locale['code'] }}', name: '{{ __($locale['label']) }}' })"
                   @if($locale['code'] === $currentLocale) aria-current="true" @endif>
                    @if($locale['code'] === $currentLocale)
                        <svg class="mr-3 h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <span class="mr-3 h-4 w-4"></span>
                    @endif

                    <div class="flex-1">
                        <div class="font-medium">{{ __($locale['label']) }}</div>
                        <div class="text-xs text-gray-500">{{ $locale['abbreviation'] }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <div aria-live="polite" aria-atomic="true" class="sr-only" x-data="{ announcement: '' }">
        <span x-text="announcement"></span>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const requiredTranslations = [
        'common.language_switcher_label',
        'common.current_language',
        'common.language_changed_to'
    ];

    const translations = {
        'common.language_switcher_label': @json(__('common.language_switcher_label')),
        'common.current_language': @json(__('common.current_language')),
        'common.language_changed_to': @json(__('common.language_changed_to'))
    };

    requiredTranslations.forEach(function(key) {
        if (translations[key] === key) {
            console.warn('Missing translation for key: ' + key);
        }
    });
});

document.addEventListener('language-changed', function(event) {
    const name = event.detail.name;
    const announcement = @json(__('common.language_changed_to')) + ' ' + name;
    const announcementEl = document.querySelector('[aria-live="polite"] span');
    if (announcementEl) {
        announcementEl.textContent = announcement;
    }
});
</script>
@endpush
