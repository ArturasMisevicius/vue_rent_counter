<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-semibold text-slate-950">System Configuration</h2>
            <p class="mt-2 text-sm text-slate-600">Grouped platform settings for superadmin review.</p>
        </section>

        @forelse ($groups as $category => $settings)
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">{{ $category }}</h3>
                <div class="mt-4 space-y-3">
                    @foreach ($settings as $setting)
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="font-medium text-slate-950">{{ $setting->label }}</p>
                            <p class="text-sm text-slate-600">{{ $setting->key }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-sm text-slate-500">No settings configured.</p>
        @endforelse
    </div>
</x-filament-panels::page>
