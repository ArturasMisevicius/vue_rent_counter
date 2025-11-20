@props(['href' => null, 'active' => false])

<li {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    @if(!$active)
        <svg class="w-3 h-3 mr-1 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
        </svg>
    @endif
    
    @if($href && !$active)
        <a href="{{ $href }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600">
            {{ $slot }}
        </a>
    @else
        <span class="text-sm font-medium {{ $active ? 'text-gray-500' : 'text-gray-700' }}">
            {{ $slot }}
        </span>
    @endif
</li>
