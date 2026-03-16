@props(['header' => null, 'caption' => null])

<div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/90 shadow-xl shadow-slate-200/60 backdrop-blur-sm">
    <div class="overflow-x-auto px-3 py-3 sm:px-4">
    <table {{ $attributes->class([
        'min-w-full border-separate border-spacing-y-3 text-sm',
        '[&_thead_th]:px-4 [&_thead_th]:py-3 [&_thead_th]:text-left [&_thead_th]:text-[11px] [&_thead_th]:font-semibold [&_thead_th]:uppercase [&_thead_th]:tracking-[0.18em] [&_thead_th]:text-slate-500',
        '[&_tbody_td]:border-y [&_tbody_td]:border-slate-200/80 [&_tbody_td]:bg-white/95 [&_tbody_td]:px-4 [&_tbody_td]:py-4 [&_tbody_td]:align-top [&_tbody_td]:text-slate-700',
        '[&_tbody_td:first-child]:rounded-l-2xl [&_tbody_td:first-child]:border-l',
        '[&_tbody_td:last-child]:rounded-r-2xl [&_tbody_td:last-child]:border-r',
        '[&_tbody_tr]:transition [&_tbody_tr]:duration-200 [&_tbody_tr:hover_td]:border-indigo-200/80 [&_tbody_tr:hover_td]:bg-slate-50/95',
    ]) }} role="table">
        @if($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        @if($header)
            <thead>
                {{ $header }}
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
    </div>
</div>
