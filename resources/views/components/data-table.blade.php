@props(['header' => null, 'caption' => null])

<div class="overflow-x-auto rounded-2xl border border-slate-200/80 shadow-lg shadow-slate-200/60">
    <table {{ $attributes->merge(['class' => 'min-w-full divide-y divide-slate-200']) }} role="table">
        @if($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        @if($header)
            <thead class="bg-gradient-to-r from-slate-50 via-white to-slate-50">
                {{ $header }}
            </thead>
        @endif
        <tbody class="bg-white divide-y divide-slate-200">
            {{ $slot }}
        </tbody>
    </table>
</div>
