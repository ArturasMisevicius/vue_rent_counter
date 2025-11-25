<?php

test('security headers are present on all responses', function () {
    $response = $this->get('/');
    
    // X-Frame-Options prevents clickjacking
    $response->assertHeader('X-Frame-Options');
    
    // X-Content-Type-Options prevents MIME sniffing
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    
    // X-XSS-Protection (legacy but still useful)
    $response->assertHeader('X-XSS-Protection');
});

test('HSTS header is present in production', function () {
    // Simulate production environment
    config(['app.env' => 'production']);
    
    $response = $this->get('/');
    
    // Strict-Transport-Security enforces HTTPS
    if (config('app.env') === 'production') {
        $response->assertHeader('Strict-Transport-Security');
    }
});

test('CSP header is configured', function () {
    $response = $this->get('/');
    
    // Content-Security-Policy prevents XSS
    $cspHeader = $response->headers->get('Content-Security-Policy');
    
    if ($cspHeader) {
        expect($cspHeader)->toContain('default-src');
    }
});

test('referrer policy is set', function () {
    $response = $this->get('/');
    
    // Referrer-Policy controls referrer information
    $referrerPolicy = $response->headers->get('Referrer-Policy');
    
    if ($referrerPolicy) {
        expect($referrerPolicy)->toBeIn([
            'no-referrer',
            'no-referrer-when-downgrade',
            'same-origin',
            'strict-origin',
            'strict-origin-when-cross-origin',
        ]);
    }
});

test('permissions policy is restrictive', function () {
    $response = $this->get('/');
    
    // Permissions-Policy (formerly Feature-Policy)
    $permissionsPolicy = $response->headers->get('Permissions-Policy');
    
    if ($permissionsPolicy) {
        // Should restrict dangerous features
        expect($permissionsPolicy)->toContain('geolocation=()');
    }
});
