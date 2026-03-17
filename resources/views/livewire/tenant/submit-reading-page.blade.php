<div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
    <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="space-y-3">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">Tenant Meter Reading</p>
            <h2 class="font-display text-3xl tracking-tight text-slate-950">Submit Reading</h2>
            <p class="max-w-2xl text-sm leading-6 text-slate-600">Pick one of your assigned meters, preview the expected consumption delta, and send the reading through the shared validation pipeline.</p>
        </div>

        @if ($successMessage)
            <div class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                {{ $successMessage }}
            </div>
        @endif

        @if ($submittedReading)
            <div class="rounded-[1.75rem] border border-emerald-200/70 bg-white px-5 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Submitted Reading</p>
                <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div class="space-y-1">
                        <p class="font-semibold text-slate-950">{{ $submittedReading['meter_name'] }}</p>
                        <p class="text-sm text-slate-500">{{ $submittedReading['meter_identifier'] }}</p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="font-display text-3xl tracking-tight text-slate-950">{{ $submittedReading['value'] }} {{ $submittedReading['unit'] }}</p>
                        <p class="text-sm text-slate-500">{{ $submittedReading['date'] }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if ($meters->isEmpty())
            <div class="rounded-[1.75rem] border border-dashed border-slate-300 px-5 py-6 text-sm text-slate-500">
                No assigned meters are available yet. Contact your property manager if this looks incorrect.
            </div>
        @else
            <form wire:submit="submit" class="space-y-5">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-2">
                        <label for="meterId" class="text-sm font-semibold text-slate-700">Meter</label>
                        <select
                            id="meterId"
                            wire:model.live="meterId"
                            @disabled($meterSelectionLocked)
                            class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                        >
                            @unless ($meterSelectionLocked)
                                <option value="">Select a meter</option>
                            @endunless
                            @foreach ($meters as $meter)
                                <option value="{{ $meter->id }}">{{ $meter->name }} · {{ $meter->identifier }}</option>
                            @endforeach
                        </select>
                        @if ($meterSelectionLocked)
                            <p class="text-sm text-slate-500">Your account has one assigned meter, so it is preselected for this submission.</p>
                        @endif
                        @error('meterId')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="readingDate" class="text-sm font-semibold text-slate-700">Reading Date</label>
                        <input
                            id="readingDate"
                            type="date"
                            wire:model.live="readingDate"
                            class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                        />
                        @error('readingDate')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="readingValue" class="text-sm font-semibold text-slate-700">Reading Value</label>
                    <input
                        id="readingValue"
                        type="number"
                        step="0.001"
                        min="0"
                        wire:model.live="readingValue"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                        placeholder="0.000"
                    />
                    @error('readingValue')
                        <p class="text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="notes" class="text-sm font-semibold text-slate-700">Notes</label>
                    <textarea
                        id="notes"
                        wire:model.live="notes"
                        rows="4"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                        placeholder="Optional notes for your property manager"
                    ></textarea>
                    @error('notes')
                        <p class="text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-brand-ink px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900"
                >
                    Submit Reading
                </button>
            </form>
        @endif
    </section>

    <aside class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Consumption Preview</p>
            <h3 class="font-display text-2xl tracking-tight text-slate-950">Consumption Preview</h3>
        </div>

        @if ($selectedMeter)
            <div class="rounded-[1.75rem] bg-slate-50 px-5 py-5">
                <p class="font-semibold text-slate-950">{{ $selectedMeter->name }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $selectedMeter->identifier }} · {{ $selectedMeter->unit }}</p>
            </div>
        @endif

        @if ($preview)
            <div class="space-y-4 rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5">
                <p class="text-sm leading-6 text-slate-600">{{ $preview['message'] }}</p>

                @if ($preview['delta'] !== null)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Estimated Consumption</p>
                        <p class="mt-2 font-display text-3xl tracking-tight text-slate-950">{{ $preview['delta'] }}</p>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-[1.75rem] border border-dashed border-slate-300 px-5 py-6 text-sm text-slate-500">
                Choose a meter and enter a value to preview the change before you submit it.
            </div>
        @endif
    </aside>
</div>
