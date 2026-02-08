<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose max-w-none dark:prose-invert">
            @php($terms = trans('filament.pages.terms'))

            <h1>{{ $terms['title'] }}</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                {{ __('filament.pages.terms.last_updated', ['date' => now()->format('F j, Y')]) }}
            </p>

            @foreach($terms['sections'] as $section)
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
                </section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
