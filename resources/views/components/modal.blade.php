@props(['name', 'title' => ''])

<div 
    x-data="{ show: false }" 
    x-on:open-modal.window="$event.detail === '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $name }}' ? show = false : null"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
>
    <!-- Backdrop -->
    <div 
        x-show="show" 
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
        @click="show = false"
    ></div>

    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div 
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 text-left shadow-xl shadow-slate-200/60 transition-all sm:my-8 sm:w-full sm:max-w-lg backdrop-blur-sm"
            @click.away="show = false"
        >
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-sky-400 to-emerald-400"></div>
            <div class="bg-white/95 px-4 pb-4 pt-6 sm:p-6 sm:pb-4">
                @if($title)
                    <h3 class="text-lg font-semibold leading-6 text-slate-900 mb-4 font-display">
                        {{ $title }}
                    </h3>
                @endif
                
                {{ $slot }}
            </div>
            
            @isset($footer)
                <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-200/80">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
