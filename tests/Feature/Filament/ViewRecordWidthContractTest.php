<?php

use Illuminate\Support\Str;

it('uses the app view record base class for every resource view page', function () {
    $viewPages = collect(glob(app_path('Filament/Resources/*/Pages/View*.php')))
        ->reject(fn (string $path): bool => Str::contains($path, '/Filament/Resources/Pages/'))
        ->values();

    expect($viewPages)->not->toBeEmpty();

    $viewPages->each(function (string $path): void {
        $contents = file_get_contents($path);

        expect($contents)
            ->toContain('use App\\Filament\\Resources\\Pages\\ViewRecord;')
            ->not->toContain('use Filament\\Resources\\Pages\\ViewRecord;');
    });
});
