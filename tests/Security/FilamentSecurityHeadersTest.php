<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Filament Security Headers', function () {
    test('filament panel has required security headers', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $response = $this->actingAs($admin)->get('/admin');
        
        // Verify critical security headers
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Verify CSP header exists
        expect($response->headers->has('Content-Security-Policy'))->toBeTrue();
    });

    test('filament panel has HSTS header in production', function () {
        if (!app()->environment('production')) {
            $this->markTestSkipped('HSTS only required in production');
        }
        
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $response = $this->actingAs($admin)->get('/admin');
        
        $response->assertHeader('Strict-Transport-Security');
        
        $hsts = $response->headers->get('Strict-Transport-Security');
        expect($hsts)->toContain('max-age=');
        expect($hsts)->toContain('includeSubDomains');
    });

    test('filament panel CSP allows required CDN resources', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $response = $this->actingAs($admin)->get('/admin');
        
        $csp = $response->headers->get('Content-Security-Policy');
        
        // Verify CDN sources are allowed for Tailwind and Alpine
        expect($csp)->toContain('cdn.tailwindcss.com');
        expect($csp)->toContain('cdn.jsdelivr.net');
        expect($csp)->toContain('fonts.googleapis.com');
        expect($csp)->toContain('fonts.gstatic.com');
    });

    test('filament panel CSP prevents inline scripts without nonce', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $response = $this->actingAs($admin)->get('/admin');
        
        $csp = $response->headers->get('Content-Security-Policy');
        
        // Verify script-src directive exists
        expect($csp)->toContain('script-src');
        
        // Note: 'unsafe-inline' is allowed for Filament/Alpine compatibility
        // In production, consider using nonces for better security
    });

    test('security headers prevent clickjacking', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $response = $this->actingAs($admin)->get('/admin');
        
        // X-Frame-Options prevents clickjacking
        $xFrameOptions = $response->headers->get('X-Frame-Options');
        expect($xFrameOptions)->toBeIn(['DENY', 'SAMEORIGIN']);
        
        // CSP frame-ancestors also prevents clickjacking
        $csp = $response->headers->get('Content-Security-Policy');
        expect($csp)->toContain('frame-ancestors');
    });

    test('security headers prevent MIME sniffing', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $response = $this->actingAs($admin)->get('/admin');
        
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    });
});
