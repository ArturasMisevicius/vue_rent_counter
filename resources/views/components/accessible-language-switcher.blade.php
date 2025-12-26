@props([
    'currentLocale' => app()->getLocale(),
    'availableLocales' => \App\Support\Localization::availableLocales(),
])

<div class="relative" x-data="{ open: false }" @click.away="open = false">
    <button
        @click="open = !open"
        type="button"
        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        aria-expanded="false"
        aria-haspopup="true"
        :aria-expanded="open"
        aria-label="{{ __('common.language_switcher_label') }}"
    >
        <span class="sr-only">{{ __('common.current_language') }}:</span>
        {{ $availableLocales->firstWhere('code', $currentLocale)['label'] ?? $currentLocale }}
        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
        role="menu"
        aria-orientation="vertical"
        aria-labelledby="language-menu"
    >
        <div class="py-1" role="none">
            @foreach($availableLocales as $locale)
                <a
                    href="{{ route('language.switch', $locale['code']) }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900 {{ $locale['code'] === $currentLocale ? 'bg-gray-50 font-medium' : '' }}"
                    role="menuitem"
                    @if($locale['code'] === $currentLocale)
                        aria-current="true"
                    @endif
                    @click="
                        open = false;
                        $dispatch('language-changed', { locale: '{{ $locale['code'] }}', label: '{{ $locale['label'] }}' });
                        // Announce to screen readers
                        $nextTick(() => {
                            const announcement = document.createElement('div');
                            announcement.setAttribute('aria-live', 'polite');
                            announcement.setAttribute('aria-atomic', 'true');
                            announcement.className = 'sr-only';
                            announcement.textContent = '{{ __('common.language_changed_to') }} ' + '{{ $locale['label'] }}';
                            document.body.appendChild(announcement);
                            setTimeout(() => document.body.removeChild(announcement), 1000);
                        });
                    "
                >
                    <span class="flex items-center">
                        <span class="text-xs font-mono mr-2 text-gray-500">{{ $locale['abbreviation'] }}</span>
                        {{ __($locale['label']) }}
                        @if($locale['code'] === $currentLocale)
                            <svg class="ml-auto h-4 w-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>

{{-- Add required translation keys to ensure accessibility --}}
@push('scripts')
<script>
    // Ensure translation keys exist for accessibility
    const requiredTranslations = [
        'common.language_switcher_label',
        'common.current_language', 
        'common.language_changed_to'
    ];
    
    // Validate translations are available
    requiredTranslations.forEach(key => {
        const translation = @json(__('common.language_switcher_label'));
        if (translation === key) {
            console.warn(`Missing translation key: ${key}`);
        }
    });
</script>
@endpush