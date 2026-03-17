<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('keeps requests actions and support classes inside the filament foundation tree', function (): void {
    $forbiddenDirectories = [
        app_path('Http/Requests'),
        app_path('Actions'),
        app_path('Support'),
    ];

    foreach ($forbiddenDirectories as $directory) {
        expect(File::isDirectory($directory))->toBeFalse();
    }

    $requiredDirectories = [
        app_path('Filament/Requests'),
        app_path('Filament/Actions'),
        app_path('Filament/Support'),
    ];

    foreach ($requiredDirectories as $directory) {
        expect(File::isDirectory($directory))->toBeTrue();
    }
});

it('does not reference legacy foundation namespaces in executable code', function (): void {
    $scanRoots = [
        app_path(),
        base_path('bootstrap'),
        base_path('config'),
        base_path('database'),
        base_path('resources'),
        base_path('routes'),
        base_path('tests'),
    ];

    $forbiddenNamespaceFragments = [
        'App\\Http\\Requests\\',
        'App\\Actions\\',
        'App\\Support\\',
    ];

    foreach ($scanRoots as $root) {
        $files = collect(File::allFiles($root))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.php'));

        foreach ($files as $file) {
            $contents = File::get($file->getPathname());

            foreach ($forbiddenNamespaceFragments as $fragment) {
                expect($contents)->not->toContain($fragment);
            }
        }
    }
});

it('keeps moved foundation classes in filament namespaces', function (): void {
    $namespaceExpectations = [
        app_path('Filament/Requests') => 'namespace App\\Filament\\Requests',
        app_path('Filament/Actions') => 'namespace App\\Filament\\Actions',
        app_path('Filament/Support') => 'namespace App\\Filament\\Support',
    ];

    foreach ($namespaceExpectations as $directory => $namespaceFragment) {
        $files = collect(File::allFiles($directory))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.php'));

        foreach ($files as $file) {
            expect(File::get($file->getPathname()))->toContain($namespaceFragment);
        }
    }
});
