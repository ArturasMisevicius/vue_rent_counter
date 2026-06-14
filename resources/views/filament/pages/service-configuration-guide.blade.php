<x-filament-panels::page>
    <section class="space-y-6">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ __('admin.service_configurations.guide.description') }}
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($this->examples() as $key => $example)
                <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900" wire:key="service-guide-{{ $key }}">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        {{ $example['title'] ?? '' }}
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        {{ $example['body'] ?? '' }}
                    </p>
                </article>
            @endforeach
        </div>
    </section>
</x-filament-panels::page>
