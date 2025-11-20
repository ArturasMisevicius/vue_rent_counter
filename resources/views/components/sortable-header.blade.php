@props(['column', 'label'])

@php
    $currentSort = request('sort', '');
    $currentDirection = request('direction', 'asc');
    $isActive = $currentSort === $column;
    $nextDirection = $isActive && $currentDirection === 'asc' ? 'desc' : 'asc';
    
    // Build query parameters preserving existing filters
    $queryParams = array_merge(request()->except(['sort', 'direction', 'page']), [
        'sort' => $column,
        'direction' => $nextDirection,
    ]);
@endphp

<th {{ $attributes->merge(['class' => 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider']) }}>
    <a href="{{ request()->url() }}?{{ http_build_query($queryParams) }}" 
       class="group inline-flex items-center space-x-1 hover:text-gray-700">
        <span>{{ $label }}</span>
        <span class="ml-2 flex-none rounded">
            @if($isActive)
                @if($currentDirection === 'asc')
                    <svg class="h-4 w-4 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                    </svg>
                @else
                    <svg class="h-4 w-4 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                @endif
            @else
                <svg class="h-4 w-4 text-gray-400 group-hover:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            @endif
        </span>
    </a>
</th>
