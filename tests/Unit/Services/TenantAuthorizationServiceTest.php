<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Services\TenantAuthorizationService;
use App\ValueObjects\TenantId;

describe('TenantAuthorizationService', function () {
    beforeEach(function () {
        $this->service = new TenantAuthorizationService();
        $this->tenantId = TenantId::from(123);
    });

    describe('canSwitchTo method', function () {
        it('returns true for superadmin', function () {
            $user = User::factory()->make(['role' => UserRole::SUPERADMIN]);

            $result = $this->service->canSwitchTo($this->tenantId, $user);

            expect($result)->toBeTrue();
        });

        it('returns true for regular user accessing their own tenant', function () {
            $user = User::factory()->make([
                'role' => UserRole::ADMIN,
                'tenant_id' => 123,
            ]);

            $result = $this->service->canSwitchTo($this->tenantId, $user);

            expect($result)->toBeTrue();
        });

        it('returns false for regular user accessing different tenant', function () {
            $user = User::factory()->make([
                'role' => UserRole::ADMIN,
                'tenant_id' => 456,
            ]);

            $result = $this->service->canSwitchTo($this->tenantId, $user);

            expect($result)->toBeFalse();
        });
    });

    describe('canAccessTenant method', function () {
        it('returns true for superadmin', function () {
            $user = User::factory()->make(['role' => UserRole::SUPERADMIN]);

            $result = $this->service->canAccessTenant($this->tenantId, $user);

            expect($result)->toBeTrue();
        });

        it('returns true for regular user accessing their own tenant', function () {
            $user = User::factory()->make([
                'role' => UserRole::ADMIN,
                'tenant_id' => 123,
            ]);

            $result = $this->service->canAccessTenant($this->tenantId, $user);

            expect($result)->toBeTrue();
        });

        it('returns false for regular user accessing different tenant', function () {
            $user = User::factory()->make([
                'role' => UserRole::ADMIN,
                'tenant_id' => 456,
            ]);

            $result = $this->service->canAccessTenant($this->tenantId, $user);

            expect($result)->toBeFalse();
        });
    });

    describe('getDefaultTenant method', function () {
        it('returns null for superadmin', function () {
            $user = User::factory()->make(['role' => UserRole::SUPERADMIN]);

            $result = $this->service->getDefaultTenant($user);

            expect($result)->toBeNull();
        });

        it('returns tenant ID for regular user', function () {
            $user = User::factory()->make([
                'role' => UserRole::ADMIN,
                'tenant_id' => 123,
            ]);

            $result = $this->service->getDefaultTenant($user);

            expect($result)->not->toBeNull();
            expect($result->getValue())->toBe(123);
        });

        it('returns null for user without tenant', function () {
            $user = User::factory()->make([
                'role' => UserRole::ADMIN,
                'tenant_id' => null,
            ]);

            $result = $this->service->getDefaultTenant($user);

            expect($result)->toBeNull();
        });
    });
});