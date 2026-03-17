<nav aria-label="Breadcrumb" class="mb-6" data-breadcrumbs="true">
    <ol class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
        @foreach ($items as $item)
            @php($url = data_get($item, 'url'))
            @php($label = data_get($item, 'label'))
            @php($isCurrent = (bool) data_get($item, 'isCurrent', $url === null))

            <li class="flex items-center gap-2">
                @if (! $loop->first)
                    <span aria-hidden="true" class="text-slate-300">/</span>
                @endif

                @if ($url !== null && ! $isCurrent)
                    <a
                        href="{{ $url }}"
                        data-breadcrumb-link="{{ $url }}"
                        class="font-medium text-slate-500 transition hover:text-slate-700"
                    >
                        {{ $label }}
                    </a>
                @else
                    <span
                        @if ($isCurrent)
                            aria-current="page"
                            data-breadcrumb-current="true"
                        @endif
                        class="font-medium text-slate-950"
                    >
                        {{ $label }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
