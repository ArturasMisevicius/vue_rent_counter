<nav aria-label="{{ __('shell.accessibility.breadcrumb') }}" class="mb-6" data-breadcrumbs="true">
    <ol class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                @if (! $loop->first)
                    <span aria-hidden="true" class="text-slate-300">/</span>
                @endif

                @if ($item->url !== null && ! $item->isCurrent)
                    <a
                        href="{{ $item->url }}"
                        data-breadcrumb-link="{{ $item->url }}"
                        class="font-medium text-slate-500 transition hover:text-slate-700"
                    >
                        {{ $item->label }}
                    </a>
                @else
                    <span
                        @if ($item->isCurrent)
                            aria-current="page"
                            data-breadcrumb-current="true"
                        @endif
                        class="font-medium text-slate-950"
                    >
                        {{ $item->label }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
