<?php

use App\Models\User;
use App\Enums\UserRole;

test('CSRF protection blocks POST requests without token', function () {
    $user = User::factory()->create(['role' => UserRole::ADMIN]);
    
    $this->actingAs($user);
    
    // Attempt POST without CSRF token
    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    
    // Should get 419 (CSRF token mismatch)
    $response->assertStatus(419);
});

test('CSRF protection allows requests with valid token', function () {
    $user = User::factory()->create(['role' => UserRole::ADMIN]);
    
    // Get CSRF token from session
    $response = $this->get('/');
    $token = $response->getSession()->token();
    
    // Make request with valid token
    $response = $this->withSession(['_token' => $token])
        ->post(route('login'), [
            '_token' => $token,
            'email' => $user->email,
            'password' => 'password',
        ]);
    
    // Should not get 419
    expect($response->status())->not->toBe(419);
});

test('CSRF protection is enabled globally', function () {
    // Verify CSRF middleware is in web middleware group
    $middleware = app('router')->getMiddlewareGroups()['web'] ?? [];
    
    expect($middleware)->toContain(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
});

test('CSRF token is present in forms', function () {
    $user = User::factory()->create(['role' => UserRole::ADMIN]);
    
    $this->actingAs($user);
    
    $response = $this->get('/');
    
    // Check for CSRF token in response
    $response->assertSee('csrf-token', false);
});
