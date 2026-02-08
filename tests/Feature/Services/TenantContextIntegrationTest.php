<?php

declare(strict_types=1);

use App\Contracts\TenantContextInterface;
use App\Enums\UserRole;
use App\Exceptions\UnauthorizedTenantSwitchException;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

describe('TenantContext Integration Tests', function () {
    beforeEach(function () {
        $this->tenantContext = app(TenantContextInterface::class);
        Log::fake();
    });

    describe('tenant context lifecycle', function () {
        it('manages complete tenant context lifecycle', function () {
            // Create test data
            $organization = Organization::factory()->create();
            $user = User::factory()->create([
                'role' => UserRole::ADMIN,
                'tenant_id' => $organization->id,
            ]);

            // Initially no context
            expect($this->tenantContext->get())->toBeNull();

            // Set context
            $this->tenantContext->set($organization->id);
            expect($this->tenantContext->get())->toBe($organization->id);

            // Validate context
            expect($this->tenantContext->validate($user))->toBeTrue();

            // Clear context
            $this->tenantContext->clear();
            expect($this->tenantContext->get())->toBeNull();

            // Verify audit logs
            Log::assertLogged('info', fn ($message) => $message === 'Tenant context set');
            Log::assertLogged('info', fn ($message) => $message === 'Tenant context cleared');
        });

        it('initializes context for user without existing context', function () {
            $organization = Organization::factory()->create();
            $user = User::factory()->create([
                'role' => UserRole::ADMIN,
                'tenant_id' => $organization->id,
            ]);

            // No initial context
            expect($this->tenantContext->get())->toBeNull();

            // Initialize
            $this->tenantContext->initialize($user);

            // Should set to user's default tenant
            expect($this->tenantContext->get())->toBe($organization->id);
        });

        it('resets invalid context during initialization', function () {
            $organization1 = Organization::factory()->create();
            $organization2 = Organization::factory()->create();
            
            $user = User::factory()->create([
                'role' => UserRole::ADMIN,
                'tenant_id' => $organization1->id,
            ]);

            // Set context to different organization
            $this->tenantContext->set($organization2->id);
            expect($this->tenantContext->get())->toBe($organization2->id);

            // Initialize should reset to user's tenant
            $this->tenantContext->initialize($user);
            expect($this->tenantContext->get())->toBe($organization1->id);

            // Verify warning log
            Log::assertLogged('warning', fn ($message) => $message === 'Invalid tenant context reset');
        });
    });

    describe('tenant switching', function () {
        it('allows superadmin to switch to any tenant', function () {
            $organization = Organization::factory()->create();
            $superadmin = User::factory()->create([
                'role' => UserRole::SUPERADMIN,
                'tenant_id' => null,
            ]);

            $this->tenantContext->switch($organization->id, $superadmin);

            expect($this->tenantContext->get())->toBe($organization->id);
            Log::assertLogged('info', fn ($message) => $message === 'Tenant context switched');
        });

        it('allows admin to switch to their own tenant', function () {
            $organization = Organization::factory()->create();
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
                'tenant_id' => $organization->id,
            ]);

            $this->tenantContext->switch($organization->id, $admin);

            expect($this->tenantContext->get())->toBe($organization->id);
        });

        it('prevents admin from switching to different tenant', function () {
            $organization1 = Organization::factory()->create();
            $organization2 = Organization::factory()->create();
            
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
                'tenant_id' => $organization1->id,
            ]);

            expect(fn () => $this->tenantContext->switch($organization2->id, $admin))
                ->toThrow(UnauthorizedTenantSwitchException::class);
        });

        it('prevents switching to non-existent tenant', function () {
            $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

            expect(fn () => $this->tenantContext->switch(99999, $user))
                ->toThrow(UnauthorizedTenantSwitchException::class);
        });
    });

    describe('authorization validation', function () {
        it('validates superadmin access to any tenant', function () {
            $organization = Organization::factory()->create();
            $superadmin = User::factory()->create([
                'role' => UserRole::SUPERADMIN,
                'tenant_id' => null,
            ]);

            $this->tenantContext->set($organization->id);

            expect($this->tenantContext->validate($superadmin))->toBeTrue();
            expect($this->tenantContext->canSwitchTo($organization->id, $superadmin))->toBeTrue();
        });

        it('validates admin access to their own tenant', function () {
            $organization = Organization::factory()->create();
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
                'tenant_id' => $organization->id,
            ]);

            $this->tenantContext->set($organization->id);

            expect($this->tenantContext->validate($admin))->toBeTrue();
            expect($this->tenantContext->canSwitchTo($organization->id, $admin))->toBeTrue();
        });

        it('prevents admin access to different tenant', function () {
            $organization1 = Organization::factory()->create();
            $organization2 = Organization::factory()->create();
            
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
                'tenant_id' => $organization1->id,
            ]);

            $this->tenantContext->set($organization2->id);

            expect($this->tenantContext->validate($admin))->toBeFalse();
            expect($this->tenantContext->canSwitchTo($organization2->id, $admin))->toBeFalse();
        });
    });

    describe('default tenant behavior', function () {
        it('returns null default tenant for superadmin', function () {
            $superadmin = User::factory()->create([
                'role' => UserRole::SUPERADMIN,
                'tenant_id' => null,
            ]);

            expect($this->tenantContext->getDefaultTenant($superadmin))->toBeNull();
        });

        it('returns user tenant as default for regular users', function () {
            $organization = Organization::factory()->create();
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
                'tenant_id' => $organization->id,
            ]);

            expect($this->tenantContext->getDefaultTenant($admin))->toBe($organization->id);
        });

        it('returns null for user without tenant', function () {
            $user = User::factory()->create([
                'role' => UserRole::ADMIN,
                'tenant_id' => null,
            ]);

            expect($this->tenantContext->getDefaultTenant($user))->toBeNull();
        });
    });

    describe('error handling', function () {
        it('throws exception for invalid tenant ID in set', function () {
            expect(fn () => $this->tenantContext->set(0))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception for non-existent tenant in set', function () {
            expect(fn () => $this->tenantContext->set(99999))
                ->toThrow(InvalidArgumentException::class);
        });

        it('handles invalid tenant ID gracefully in canSwitchTo', function () {
            $user = User::factory()->create();

            expect($this->tenantContext->canSwitchTo(0, $user))->toBeFalse();
            expect($this->tenantContext->canSwitchTo(-1, $user))->toBeFalse();
        });
    });
});