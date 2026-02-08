<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ApiAuthenticationService;

describe('API Authentication', function () {
    beforeEach(function () {
        $this->authService = app(ApiAuthenticationService::class);
    });

    describe('POST /api/auth/login', function () {
        it('authenticates user with valid credentials', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
                'role' => UserRole::ADMIN,
                'is_active' => true,
            ]);

            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
                'token_name' => 'test-token',
            ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => ['id', 'name', 'email', 'role'],
                        'token',
                        'abilities',
                        'expires_at',
                    ],
                    'message',
                ]);

            expect($response->json('success'))->toBeTrue();
            expect($response->json('data.user.email'))->toBe('test@example.com');
            expect($response->json('data.token'))->toBeString();
        });

        it('rejects invalid credentials', function () {
            User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
            ]);

            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

            $response->assertUnauthorized()
                ->assertJson([
                    'success' => false,
                ]);
        });

        it('rejects inactive users', function () {
            User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('password123'),
                'is_active' => false,
            ]);

            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

            $response->assertUnauthorized();
        });

        it('validates required fields', function () {
            $response = $this->postJson('/api/auth/login', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['email', 'password']);
        });
    });

    describe('POST /api/auth/logout', function () {
        it('revokes user tokens', function () {
            $user = User::factory()->create();
            $token = $user->createApiToken('test-token');

            $response = $this->withToken($token)
                ->postJson('/api/auth/logout');

            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'Tokens revoked successfully',
                ]);

            expect($user->fresh()->tokens()->count())->toBe(0);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/auth/logout');

            $response->assertUnauthorized();
        });
    });

    describe('GET /api/auth/me', function () {
        it('returns authenticated user information', function () {
            $user = User::factory()->create(['role' => UserRole::ADMIN]);
            $token = $user->createApiToken('test-token');

            $response = $this->withToken($token)
                ->getJson('/api/auth/me');

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => ['id', 'name', 'email', 'role'],
                        'abilities',
                        'tokens',
                    ],
                ]);

            expect($response->json('data.user.id'))->toBe($user->id);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/auth/me');

            $response->assertUnauthorized();
        });
    });

    describe('POST /api/auth/refresh', function () {
        it('refreshes user token', function () {
            $user = User::factory()->create();
            $oldToken = $user->createApiToken('test-token');

            $response = $this->withToken($oldToken)
                ->postJson('/api/auth/refresh', [
                    'token_name' => 'test-token',
                ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'token',
                        'expires_at',
                    ],
                ]);

            expect($response->json('data.token'))->not->toBe($oldToken);
        });
    });

    describe('Role-based abilities', function () {
        it('assigns correct abilities for superadmin', function () {
            $abilities = $this->authService->getAbilitiesForRole(UserRole::SUPERADMIN);

            expect($abilities)->toContain('*');
        });

        it('assigns correct abilities for admin', function () {
            $abilities = $this->authService->getAbilitiesForRole(UserRole::ADMIN);

            expect($abilities)->toContain('meter-reading:read');
            expect($abilities)->toContain('meter-reading:write');
            expect($abilities)->toContain('property:read');
            expect($abilities)->not->toContain('*');
        });

        it('assigns limited abilities for tenant', function () {
            $abilities = $this->authService->getAbilitiesForRole(UserRole::TENANT);

            expect($abilities)->toContain('meter-reading:read');
            expect($abilities)->not->toContain('property:write');
            expect($abilities)->not->toContain('*');
        });
    });
});