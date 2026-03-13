<?php

/**
 * Framework Version Verification Tests
 * 
 * Validates that the application is running on the correct framework versions
 * after the upgrade to Laravel 12.x and Filament 4.x.
 * 
 * Requirements: 1.1 - Laravel 12.x verification
 */

use Illuminate\Foundation\Application;

test('Laravel version is 12.x or higher', function () {
    $version = app()->version();
    
    expect($version)
        ->toMatch('/^12\./')
        ->and($version)
        ->toBeString();
});

test('Laravel application instance is available', function () {
    $app = app();
    
    expect($app)
        ->toBeInstanceOf(Application::class);
});

test('Laravel version can be retrieved', function () {
    $version = app()->version();
    
    expect($version)
        ->not->toBeEmpty()
        ->and($version)
        ->toContain('.');
});
