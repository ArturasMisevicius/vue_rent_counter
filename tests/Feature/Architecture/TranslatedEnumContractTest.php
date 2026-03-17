<?php

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses()->group('architecture');

/**
 * @return array<int, class-string<BackedEnum&HasLabel>>
 */
function applicationEnumClasses(): array
{
    return collect(File::allFiles(app_path('Enums')))
        ->reject(fn ($file) => str_contains($file->getRelativePathname(), 'Concerns/'))
        ->map(function ($file): string {
            $relativePath = Str::beforeLast($file->getRelativePathname(), '.php');
            $classPath = str_replace('/', '\\', $relativePath);

            return 'App\\Enums\\'.$classPath;
        })
        ->filter(fn (string $class): bool => enum_exists($class))
        ->sort()
        ->values()
        ->all();
}

it('keeps every application enum label-aware and backed', function () {
    foreach (applicationEnumClasses() as $enumClass) {
        expect(is_subclass_of($enumClass, HasLabel::class))
            ->toBeTrue("Expected {$enumClass} to implement ".HasLabel::class);

        expect((new ReflectionEnum($enumClass))->isBacked())
            ->toBeTrue("Expected {$enumClass} to be a backed enum.");
    }
});

it('keeps enum options and labels in sync for every supported locale', function () {
    $originalLocale = app()->getLocale();

    try {
        foreach (['en', 'lt', 'ru'] as $locale) {
            app()->setLocale($locale);

            foreach (applicationEnumClasses() as $enumClass) {
                $expectedValues = array_map(
                    static fn (BackedEnum $case): string|int => $case->value,
                    $enumClass::cases(),
                );

                $options = $enumClass::options();

                expect(array_keys($options))
                    ->toBe($expectedValues, "Expected {$enumClass}::options() keys to match enum case values for locale [{$locale}].");

                foreach ($enumClass::cases() as $case) {
                    $label = $case->label();

                    expect($label)
                        ->toBeString()
                        ->not->toBe('')
                        ->and($label)
                        ->not->toStartWith('enums.');

                    expect($options[$case->value] ?? null)
                        ->toBe($label, "Expected {$enumClass} option label to match {$case->name} label for locale [{$locale}].");
                }
            }
        }
    } finally {
        app()->setLocale($originalLocale);
    }
});
