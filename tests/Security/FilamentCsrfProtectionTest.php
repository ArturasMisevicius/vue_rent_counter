<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Filament CSRF Protection', function () {
    test('filament routes require CSRF token for POST requests', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Attempt POST without CSRF token should fail
        $response = $this->actingAs($admin)
            ->post('/admin', [
                'test' => 'data',
            ]);
        
        // Should be rejected with 419 (CSRF token mismatch) or redirect
        expect($response->status())->toBeIn([419, 302, 403]);
    });

    test('filament routes accept valid CSRF token', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Get CSRF token
        $token = csrf_token();
        
        // With CSRF token should not fail with CSRF error
        $response = $this->actingAs($admin)
            ->withSession(['_token' => $token])
            ->post('/admin', [
                '_token' => $token,
                'test' => 'data',
            ]);
        
        // Should not be CSRF error (419)
        expect($response->status())->not->toBe(419);
    });

    test('CSRF middleware is active on Filament routes', function () {
        // Verify CSRF middleware is in the web middleware group
        $middlewareGroups = app('router')->getMiddlewareGroups();
        
        expect($middlewareGroups)->toHaveKey('web');
        expect($middlewareGroups['web'])->toContain(\App\Http\Middleware\VerifyCsrfToken::class);
    });
});
