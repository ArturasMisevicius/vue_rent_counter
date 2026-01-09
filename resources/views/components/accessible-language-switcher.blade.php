{{--
    Accessible Language Switcher Component
    
    A fully accessible dropdown component for switching application locale.
    Integrates with Laravel's localization system and provides screen reader support.
    
    @props array $availableLocales Collection of available locales from Localization::availableLocales()
    @props string $currentLocale Current application locale (defaults to app()->getLocale())
    
    Features:
    - WCAG 2.1 AA compliant accessibility
    - Screen reader announcements for language changes
    - Keyboard navigation support
    - Visual indicators for current language
    - Alpine.js powered interactions
    - Translation validation
    
    Usage:
    <x-accessible-language-switcher 
        :available-locales="$availableLocales" 
        :current-locale="$currentLocale" 
    />
    
    Dependencies:
    - Alpine.js for interactivity
    - Tailwind CSS for styling
    - Laravel localization system
    - Translation keys: common.language_switcher_label, common.current_language, common.language_changed_to
    
    @see \App\View\Composers\NavigationComposer For data preparation
    @see \App\Support\Localization For locale configuration
    @see \App\Http\Controllers\LanguageController For language switching
--}}

@props([
    'currentLocale' => app()->getLocale(),
    'availableLocales' => \App\Support\Localization::availableLocales(),
])
    - WCAG 2.1 AA compliant accessibility
    - Screen reader announcements for language changes
    - Keyboard navigation support
    - Visual indicators for current language
    - Alpine.js powered interactions
    - Translation validation
    
    Usage:
    <x-accessible-language-switcher 
        :available-locales="$availableLocales" 
        :current-locale="$currentLocale" 
    />
    
    Dependencies:
    - Alpine.js for interactivity
    - Tailwind CSS for styling
    - Laravel localization system
    - Translation keys: common.language_switcher_label, common.current_language, common.language_changed_to
    
    @see \App\View\Composers\NavigationComposer For data preparation
    @see \App\Support\Localization For locale configuration
    @see \App\Http\Controllers\LanguageController For language switching
--}}

@props([
    'currentLocale' => app()->getLocale(),
    'availableLocales' => \App\Support\Localization::availableLocales(),
])
<div class="relative inline-block text-left" x-data="{ open: false }" @click.away="open = false">
    {{-- Button --}}
    <div>
        <button type="button" 
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                @click="open = !open"
                :aria-expanded="open"
                aria-haspopup="true"
                aria-label="{{ __('common.language_switcher_label') }}">
            
            {{-- Current Language Display --}}
            @php
                $currentLocaleConfig = $availableLocales->firstWhere('code', $currentLocale);
            @endphp
            
            @if($currentLocaleConfig)
                <span class="mr-2">{{ __($currentLocaleConfig['label']) }}</span>
                <span class="text-xs text-gray-500">({{ $currentLocaleConfig['abbreviation'] }})</span>
            @else
                <span class="mr-2">{{ __('common.language') }}</span>
            @endif
            
            {{-- Chevron Icon --}}
            <svg class="ml-2 -mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            
            {{-- Screen Reader Text --}}
            <span class="sr-only">{{ __('common.current_language') }}: {{ $currentLocaleConfig ? __($currentLocaleConfig['label']) : __('common.language') }}</span>
        </button>
    </div>

    {{-- Dropdown Menu --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
         role="menu"
         aria-orientation="vertical"
         aria-labelledby="language-menu">
        
        <div class="py-1" role="none">
            @foreach($availableLocales as $locale)
                <a href="{{ route('language.switch', $locale['code']) }}"
                   class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"
                   role="menuitem"
                   @click="$dispatch('language-changed', { locale: '{{ $locale['code'] }}', name: '{{ __($locale['label']) }}' })"
                   @if($locale['code'] === $currentLocale) aria-current="true" @endif>
                   
                    {{-- Check Mark for Current Language --}}
                    @if($locale['code'] === $currentLocale)
                        <svg class="mr-3 h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    @else
                        <span class="mr-3 h-4 w-4"></span>
                    @endif
                    
                    {{-- Language Name and Abbreviation --}}
                    <div class="flex-1">
                        <div class="font-medium">{{ __($locale['label']) }}</div>
                        <div class="text-xs text-gray-500">{{ $locale['abbreviation'] }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    
    {{-- Screen Reader Announcement Area --}}
    <div aria-live="polite" aria-atomic="true" class="sr-only" x-data="{ announcement: '' }">
        <span x-text="announcement"></span>
    </div>
</div>

{{-- Translation Validation Script --}}
@push('scripts')
<script>
    // Validate required translations are available
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
    
    // Handle language change events
    document.addEventListener('language-changed', function(event) {
        const { locale, name } = event.detail;
        
        // Announce to screen readers
        const announcement = @json(__('common.language_changed_to')) + ' ' + name;
        const announcementEl = document.querySelector('[aria-live="polite"] span');
        if (announcementEl) {
            announcementEl.textContent = announcement;
        }
    });
</script>
@endpush