<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('PII Protection', function () {
    test('user passwords are never exposed in API responses', function () {
        $user = User::factory()->create(['password' => 'secret123']);
        
        $json = $user->toArray();
        
        expect($json)->not->toHaveKey('password');
        expect($json)->not->toHaveKey('remember_token');
    });

    test('user passwords are never exposed in JSON serialization', function () {
        $user = User::factory()->create(['password' => 'secret123']);
        
        $json = $user->toJson();
        
        expect($json)->not->toContain('secret123');
        expect($json)->not->toContain('password');
    });

    test('hidden fields are not included in model arrays', function () {
        $user = User::factory()->create();
        
        $array = $user->toArray();
        
        expect($array)->not->toHaveKey('password');
        expect($array)->not->toHaveKey('remember_token');
    });

    test('user input is escaped to prevent XSS', function () {
        $user = User::factory()->create([
            'name' => '<script>alert("XSS")</script>',
        ]);
        
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $response = $this->actingAs($admin)->get('/admin/users');
        
        // Script tags should be escaped
        $response->assertDontSee('<script>', false);
        $response->assertSee('&lt;script&gt;', false);
    });

    test('mass assignment protection prevents unauthorized field updates', function () {
        $user = User::factory()->create();
        
        $originalId = $user->id;
        $originalToken = $user->remember_token;
        
        // Attempt to mass assign protected fields
        $user->fill([
            'id' => 999,
            'remember_token' => 'hacked',
            'email_verified_at' => now(),
        ]);
        
        // Protected fields should not be updated
        expect($user->id)->toBe($originalId);
        expect($user->remember_token)->toBe($originalToken);
    });
});
