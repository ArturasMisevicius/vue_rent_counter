<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('loads Alpine.js from bundled assets', function () {
    $response = get('/');
    
    $response->assertStatus(200);
    
    // Should NOT contain CDN script
    $response->assertDontSee('cdn.jsdelivr.net/npm/alpinejs');
    
    // Should include the compiled Vite entry (not a CDN script)
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
    $jsEntry = 'build/' . $manifest['resources/js/app.js']['file'];
    
    $response->assertSee($jsEntry);
});

it('has compiled Vite manifest', function () {
    expect(file_exists(public_path('build/manifest.json')))->toBeTrue();
    
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
    
    expect($manifest)->toHaveKey('resources/js/app.js');
});

it('includes Alpine.js in compiled bundle', function () {
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
    $jsFile = public_path('build/' . $manifest['resources/js/app.js']['file']);
    
    expect(file_exists($jsFile))->toBeTrue();
    
    $content = file_get_contents($jsFile);
    
    // Check for Alpine.js code patterns
    expect($content)->toContain('Alpine');
});
