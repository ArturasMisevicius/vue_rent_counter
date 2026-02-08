<div class="relative" x-data="{ 
    showResults: @entangle('showResults'),
    isActive: @entangle('isActive'),
    hideTimeout: null,
    
    hideResultsDelayed() {
        this.hideTimeout = setTimeout(() => {
            this.showResults = false;
            this.isActive = false;
        }, 200);
    },
    
    cancelHide() {
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }
    }
}" 
x-on:hide-results-delayed.window="hideResultsDelayed()"
@if(!$this->canSearch())
    style="display: none;"
@endif>

    {{-- Search Input --}}
    <div class="relative">
        <div class="relative">
            {{-- Search Icon --}}
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
            </div>

            {{-- Input Field --}}
            <input 
                type="text" 
                wire:model.live.debounce.300ms="query"
                wire:focus="focusSearch"
                wire:blur="blurSearch"
                x-on:focus="isActive = true; cancelHide()"
                x-on:blur="hideResultsDelayed()"
                placeholder="Search organizations, users, properties..."
                class="block w-full pl-10 pr-10 py-2 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                autocomplete="off"
                spellcheck="false"
            >

            {{-- Loading Spinner --}}
            <div wire:loading wire:target="query" class="absolute inset-y-0 right-0 flex items-center pr-3">
                <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            {{-- Clear Button --}}
            @if(!empty($query))
                <button 
                    type="button"
                    wire:click="clearSearch"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    wire:loading.class="hidden"
                    wire:target="query"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif
        </div>
    </div>

    {{-- Search Results Dropdown --}}
    <div 
        x-show="showResults && (Object.keys(@js($results)).length > 0 || @js($suggestions).length > 0)"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-on:mouseenter="cancelHide()"
        x-on:mouseleave="hideResultsDelayed()"
        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg dark:bg-gray-800 dark:border-gray-600 max-h-96 overflow-y-auto"
        style="display: none;"
    >
        {{-- Search Results --}}
        @if(!empty($results))
            <div class="p-2">
                {{-- Results Header --}}
                <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600 dark:text-gray-400">
                    {{ $this->getTotalResultsCount() }} {{ Str::plural('result', $this->getTotalResultsCount()) }} found
                </div>

                {{-- Grouped Results --}}
                @foreach($results as $groupName => $group)
                    <div class="mt-2">
                        {{-- Group Header --}}
                        <div class="px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 rounded">
                            {{ $groupName }} ({{ $group['count'] }})
                        </div>

                        {{-- Group Results --}}
                        <div class="mt-1">
                            @foreach($group['results'] as $result)
                                <button
                                    type="button"
                                    wire:click="navigateToResult('{{ $result['url'] }}')"
                                    class="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none rounded transition-colors duration-150"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            {{-- Result Title --}}
                                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ $result['title'] }}
                                            </div>
                                            
                                            {{-- Result Details --}}
                                            @if(!empty($result['details']))
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    @foreach($result['details'] as $key => $value)
                                                        <span class="inline-block mr-3">
                                                            <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                            {{ $value }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Relevance Indicator --}}
                                        @if($result['relevance_score'] > 0)
                                            <div class="ml-2 flex-shrink-0">
                                                <div class="flex items-center">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <svg class="w-3 h-3 {{ $i <= min(5, ceil($result['relevance_score'] / 2)) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                    @endfor
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Search Suggestions --}}
        @if(!empty($suggestions) && empty($results))
            <div class="p-2">
                <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200 dark:border-gray-600 dark:text-gray-400">
                    Suggestions
                </div>
                <div class="mt-2">
                    @foreach($suggestions as $suggestion)
                        <button
                            type="button"
                            wire:click="useSuggestion('{{ $suggestion }}')"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none rounded transition-colors duration-150"
                        >
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                {{ $suggestion }}
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- No Results --}}
        @if(empty($results) && empty($suggestions) && !empty($query) && strlen($query) >= 2)
            <div class="p-4 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No results found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Try searching for organizations, users, properties, buildings, meters, or invoices.
                </p>
                <div class="mt-3">
                    <button
                        type="button"
                        wire:click="clearSearch"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:text-blue-200 dark:bg-blue-800 dark:hover:bg-blue-700"
                    >
                        Clear search
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Keyboard Shortcuts Help --}}
    @if($isActive && empty($query))
        <div 
            x-show="isActive"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg dark:bg-gray-800 dark:border-gray-600"
            style="display: none;"
        >
            <div class="p-4">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    <div class="font-medium mb-2">Search across:</div>
                    <ul class="space-y-1 text-xs">
                        <li>• Organizations (name, email, domain)</li>
                        <li>• Users (name, email, organization)</li>
                        <li>• Properties (address, unit number)</li>
                        <li>• Buildings (name, address)</li>
                        <li>• Meters (serial number)</li>
                        <li>• Invoices (invoice number, reference)</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>