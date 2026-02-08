@props(['column', 'label'])

<th {{ $attributes->merge(['class' => 'px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide']) }}>
    <a href="{{ request()->url() }}?{{ http_build_query(array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => (request('sort', '') === $column && request('direction', 'asc') === 'asc') ? 'desc' : 'asc'])) }}"
       class="group inline-flex items-center space-x-1 hover:text-slate-900 transition">
        <span>{{ $label }}</span>
        <span class="ml-2 flex-none rounded">
            @if(request('sort', '') === $column)
                @if(request('direction', 'asc') === 'asc')
                    <svg class="h-4 w-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                    </svg>
                @else
                    <svg class="h-4 w-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                @endif
            @else
                <svg class="h-4 w-4 text-slate-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            @endif
        </span>
    </a>
</th>
