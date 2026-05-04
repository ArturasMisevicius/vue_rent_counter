<?php

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('configures filament date and datetime fields as localized modal pickers', function (): void {
    app()->setLocale('lt');

    $dateField = DatePicker::make('billing_period_start');
    $dateTimeField = DateTimePicker::make('paid_at');

    expect($dateField->isNative())
        ->toBeFalse()
        ->and($dateField->shouldCloseOnDateSelection())
        ->toBeTrue()
        ->and($dateField->getLocale())
        ->toBe('lt')
        ->and($dateField->getFirstDayOfWeek())
        ->toBe(1)
        ->and($dateField->getDisplayFormat())
        ->toBe('Y-m-d')
        ->and($dateTimeField->isNative())
        ->toBeFalse()
        ->and($dateTimeField->hasSeconds())
        ->toBeFalse()
        ->and($dateTimeField->getMinutesStep())
        ->toBe(5)
        ->and($dateTimeField->getLocale())
        ->toBe('lt')
        ->and($dateTimeField->getFirstDayOfWeek())
        ->toBe(1)
        ->and($dateTimeField->getDisplayFormat())
        ->toBe('Y-m-d H:i');
});

it('keeps blade frontend date selection on the shared calendar modal instead of native date inputs', function (): void {
    $bladeFiles = collect(File::allFiles(resource_path('views')))
        ->filter(fn ($file): bool => Str::endsWith($file->getFilename(), '.blade.php'));

    foreach ($bladeFiles as $file) {
        $contents = File::get($file->getPathname());

        expect($contents)
            ->not->toContain('type="date"')
            ->and($contents)
            ->not->toContain("type='date'")
            ->and($contents)
            ->not->toContain('type="datetime-local"')
            ->and($contents)
            ->not->toContain("type='datetime-local'");
    }
});

it('provides shared calendar copy for every supported locale', function (): void {
    foreach (['en', 'lt', 'es', 'ru'] as $locale) {
        foreach (['choose_date', 'choose_datetime', 'open', 'close', 'today', 'done'] as $key) {
            $translationKey = "calendar.{$key}";

            expect(__($translationKey, [], $locale))
                ->not->toBe($translationKey)
                ->not->toBe('');
        }
    }
});
