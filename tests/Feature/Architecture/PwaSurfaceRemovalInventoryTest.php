<?php

it('removes pwa package references from composer manifests', function (): void {
    expect(file_get_contents(base_path('composer.json')))
        ->not->toContain('erag/laravel-pwa')
        ->and(file_get_contents(base_path('composer.lock')))
        ->not->toContain('erag/laravel-pwa');
});

it('removes pwa config and public assets', function (): void {
    expect(file_exists(config_path('pwa.php')))->toBeFalse()
        ->and(file_exists(public_path('manifest.json')))->toBeFalse()
        ->and(file_exists(public_path('offline.html')))->toBeFalse()
        ->and(file_exists(public_path('sw.js')))->toBeFalse();
});

it('returns not found for the manifest and service worker endpoints', function (): void {
    $this->get('/manifest.json')->assertNotFound();
    $this->get('/sw.js')->assertNotFound();
});
