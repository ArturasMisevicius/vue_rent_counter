{{-- Language Switcher Component --}}
{{-- Public properties available: $variant, $class, $showLabels, $currentLocale, $languageData, $currentLanguageData --}}

@if($languageData->isNotEmpty())
    @if($variant === 'select')
        <div class="inline-flex {{ $class }}">
            <form method="GET" action="{{ route('language.switch', ['locale' => $currentLocale]) }}" class="inline-flex">
                <label for="language-select" class="sr-only">{{ __('common.select_language') }}</label>
                <select
                    id="language-select"
                    name="locale"
                    data-language-switcher="true"
                    data-base-url="{{ url('/language') }}/"
                    class="block w-full rounded-md border-0 bg-white/10 py-1.5 pl-3 pr-10 text-white ring-1 ring-inset ring-white/20 focus:ring-2 focus:ring-inset focus:ring-white sm:text-sm sm:leading-6"
                    aria-label="{{ __('common.select_language') }}"
                >
                    @foreach($languageData as $language)
                        <option
                            value="{{ $language->code }}"
                            @if($language->matches($currentLocale)) selected @endif
                        >
                            {{ $showLabels ? $language->getDisplayName() : $language->getUppercaseCode() }}
                        </option>
                    @endforeach
                </select>
                <noscript>
                    <button type="submit" class="ml-2 px-2 py-1 text-xs bg-white/20 rounded hover:bg-white/30 transition-colors">
                        {{ __('common.change_language') }}
                    </button>
                </noscript>
            </form>
        </div>
    @else
        {{-- Dropdown variant --}}
        <div class="relative inline-block text-left {{ $class }}" x-data="{ open: false }">
            <button
                @click="open = !open"
                @keydown.escape="open = false"
                class="inline-flex items-center gap-x-1.5 rounded-md bg-white/10 px-3 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-white/20 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/40 transition-all duration-200"
                aria-expanded="false"
                aria-haspopup="true"
                aria-label="{{ __('common.select_language') }}"
                type="button"
            >
                @if($currentLanguageData)
                    {{ $showLabels ? $currentLanguageData->getDisplayName() : $currentLanguageData->getUppercaseCode() }}
                @else
                    {{ strtoupper($currentLocale) }}
                @endif
                <svg class="ml-1 h-4 w-4 transition-transform duration-200"
                     :class="{ 'rotate-180': open }"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24"
                     aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="open"
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none"
                role="menu"
                aria-orientation="vertical"
                x-cloak
            >
                @foreach($languageData as $language)
                    <a
                        href="{{ route('language.switch', $language->code) }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none {{ $language->matches($currentLocale) ? 'bg-gray-50 font-medium' : '' }}"
                        role="menuitem"
                        @click="open = false"
                        @if($language->matches($currentLocale))
                            aria-current="true"
                        @endif
                    >
                        <span class="flex items-center justify-between">
                            {{ $showLabels ? $language->getDisplayName() : $language->getUppercaseCode() }}
                            @if($language->matches($currentLocale))
                                <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@else
    {{-- No languages available --}}
    <div class="inline-flex {{ $class }}" role="status" aria-live="polite">
        <span class="text-sm text-gray-500">{{ __('common.no_languages_available') }}</span>
    </div>
@endif
