<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Services\TenantAuditLogger;
use App\ValueObjects\TenantId;
use Illuminate\Support\Facades\Log;

describe('TenantAuditLogger', function () {
    beforeEach(function () {
        $this->logger = new TenantAuditLogger();
        $this->tenantId = TenantId::from(123);

        Log::spy();
    });

    describe('logContextSet method', function () {
        it('logs tenant context set with correct data', function () {
            $this->logger->logContextSet($this->tenantId);

            Log::shouldHaveReceived('info')
                ->once()
                ->with('Tenant context set', \Mockery::on(function (array $context): bool {
                    return $context['tenant_id'] === 123 &&
                           array_key_exists('user_id', $context);
                }));
        });
    });

    describe('logContextSwitch method', function () {
        it('logs tenant context switch with the provided metadata', function () {
            $user = User::factory()->make([
                'id' => 456,
                'role' => UserRole::ADMIN,
            ]);

            $previousTenantId = TenantId::from(789);
            $organizationName = 'Test Organization';

            $this->logger->logContextSwitch($user, $this->tenantId, $previousTenantId, $organizationName);

            Log::shouldHaveReceived('info')
                ->once()
                ->with('Tenant context switched', \Mockery::on(function (array $context): bool {
                    return $context['user_id'] === 456 &&
                           $context['previous_tenant_id'] === 789 &&
                           $context['new_tenant_id'] === 123 &&
                           $context['organization_name'] === 'Test Organization';
                }));
        });

        it('logs tenant context switch without a previous tenant', function () {
            $user = User::factory()->make([
                'id' => 456,
                'role' => UserRole::ADMIN,
            ]);

            $this->logger->logContextSwitch($user, $this->tenantId, null, 'Test Organization');

            Log::shouldHaveReceived('info')
                ->once()
                ->with('Tenant context switched', \Mockery::on(function (array $context): bool {
                    return $context['previous_tenant_id'] === null &&
                           $context['new_tenant_id'] === 123 &&
                           $context['organization_name'] === 'Test Organization';
                }));
        });
    });

    describe('logContextCleared method', function () {
        it('logs context cleared when previous tenant exists', function () {
            $this->logger->logContextCleared($this->tenantId);

            Log::shouldHaveReceived('info')
                ->once()
                ->with('Tenant context cleared', \Mockery::on(function (array $context): bool {
                    return $context['previous_tenant_id'] === 123 &&
                           array_key_exists('user_id', $context);
                }));
        });

        it('logs context cleared even when there is no previous tenant', function () {
            $this->logger->logContextCleared(null);

            Log::shouldHaveReceived('info')
                ->once()
                ->with('Tenant context cleared', \Mockery::on(function (array $context): bool {
                    return $context['previous_tenant_id'] === null &&
                           array_key_exists('user_id', $context);
                }));
        });
    });

    describe('logInvalidContextReset method', function () {
        it('logs invalid context reset with reset tenant', function () {
            $user = User::factory()->make([
                'id' => 456,
            ]);

            $invalidTenantId = TenantId::from(999);
            $newTenantId = TenantId::from(123);

            $this->logger->logInvalidContextReset($user, $invalidTenantId, $newTenantId);

            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Invalid tenant context reset', \Mockery::on(function (array $context): bool {
                    return $context['user_id'] === 456 &&
                           $context['invalid_tenant_id'] === 999 &&
                           $context['new_tenant_id'] === 123;
                }));
        });

        it('logs invalid context reset without reset tenant', function () {
            $user = User::factory()->make([
                'id' => 456,
            ]);

            $invalidTenantId = TenantId::from(999);

            $this->logger->logInvalidContextReset($user, $invalidTenantId, null);

            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Invalid tenant context reset', \Mockery::on(function (array $context): bool {
                    return $context['new_tenant_id'] === null;
                }));
        });
    });
});
