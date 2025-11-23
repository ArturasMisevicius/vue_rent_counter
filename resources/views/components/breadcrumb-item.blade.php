@props(['href' => null, 'active' => false])

<li {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    @if(!$active)
        <svg class="w-3 h-3 mr-2 text-slate-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
        </svg>
    @endif

    @if($href && !$active)
        <a href="{{ $href }}" class="inline-flex items-center text-sm font-semibold text-slate-700 hover:text-indigo-600 px-2 py-1 rounded-full hover:bg-slate-100 transition">
            {{ $slot }}
        </a>
    @else
        <span class="px-2 py-1 text-sm font-semibold {{ $active ? 'text-slate-500' : 'text-slate-700' }}">
            {{ $slot }}
        </span>
    @endif
</li>
