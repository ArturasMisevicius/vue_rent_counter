@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-white/90 border border-slate-200/80 shadow-lg shadow-slate-200/60 rounded-2xl p-6 backdrop-blur-sm transition duration-200']) }}>
    @if($title)
        <h3 class="text-lg font-semibold text-slate-900 mb-4 font-display flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-gradient-to-r from-indigo-500 to-sky-500"></span>
            {{ $title }}
        </h3>
    @endif
    
    {{ $slot }}
</div>
