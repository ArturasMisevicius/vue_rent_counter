<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose max-w-none dark:prose-invert">
            @php($privacy = trans('filament.pages.privacy'))

            <h1>{{ $privacy['title'] }}</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                {{ __('filament.pages.privacy.last_updated', ['date' => now()->format('F j, Y')]) }}
            </p>

            @foreach($privacy['sections'] as $section)
                <section>
                    <h2>{{ $section['title'] }}</h2>

                    @if(!empty($section['body']))
                        <p>{!! $section['body'] !!}</p>
                    @endif

                    @if(!empty($section['items']))
                        <ul>
                            @foreach($section['items'] as $item)
                                <li>{!! $item !!}</li>
                            @endforeach
                        </ul>
                    @endif

                    @foreach($section['subsections'] ?? [] as $subsection)
                        <h3>{{ $subsection['title'] }}</h3>
                        @if(!empty($subsection['body']))
                            <p>{!! $subsection['body'] !!}</p>
                        @endif
                        @if(!empty($subsection['items']))
                            <ul>
                                @foreach($subsection['items'] as $item)
                                    <li>{!! $item !!}</li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach

                    @if(!empty($section['note']))
                        <p>{!! $section['note'] !!}</p>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
