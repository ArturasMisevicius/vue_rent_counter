<?php

declare(strict_types=1);

use App\Contracts\TenantAuditLoggerInterface;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\TenantAuditLogger;
use App\ValueObjects\TenantId;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Log;

describe('TenantAuditLogger', function () {
    beforeEach(function () {
        $this->session = \Mockery::mock(Session::class);
        $this->logger = new TenantAuditLogger($this->session);
        $this->tenantId = TenantId::from(123);
        
        // Use Log::spy() instead of Log::fake() for Laravel 12 compatibility
        Log::spy();
        
        $this->session
            ->shouldReceive('getId')
            ->andReturn('test-session-id');
    });

    afterEach(function () {
        \Mockery::close();
    });

    describe('logContextSet method', function () {
        it('logs tenant context set with correct data', function () {
            $this->logger->logContextSet($this->tenantId);

            Log::shouldHaveReceived('info')
                ->once()
                ->with('Tenant context set', \Mockery::on(function ($context) {
                    return $context['tenant_id'] === 123 &&
                           $context['session_id'] === 'test-session-id' &&
                           isset($context['timestamp']);
                }));
        });
    });

    describe('logContextSwitch method', function () {
        it('logs tenant context switch with full data', function () {
            $user = User::factory()->make([
                'id' => 456,
                'email' => 'test@example.com',
                'role' => UserRole::ADMIN,
            ]);
            
            $previousTenantId = TenantId::from(789);
            $tenantName = 'Test Organization';

            $this->logger->logContextSwitch($user, $this->tenantId, $previousTenantId, $tenantName);

            Log::shouldHaveReceived('info')
                ->once()
                ->with('Tenant context switched', \Mockery::on(function ($context) {
                    return $context['user_id'] === 456 &&
                           $context['user_email'] === 'test@example.com' &&
                           $context['user_role'] === 'admin' &&
                           $context['previous_tenant_id'] === 789 &&
                           $context['new_tenant_id'] === 123 &&
                           $context['tenant_name'] === 'Test Organization' &&
                           $context['session_id'] === 'test-session-id';
                }));
        });

        it('logs tenant context switch without previous tenant', function () {
            $user = User::factory()->make([
                'id' => 456,
                'email' => 'test@example.com',
                'role' => UserRole::ADMIN,
            ]);

            $this->logger->logContextSwitch($user, $this->tenantId);

            Log::shouldHaveReceived('info')
                ->once()
                ->with('Tenant context switched', \Mockery::on(function ($context) {
                    return $context['previous_tenant_id'] === null &&
                           $context['new_tenant_id'] === 123;
                }));
        });
    });

    describe('logContextCleared method', function () {
        it('logs context cleared when previous tenant exists', function () {
            $this->logger->logContextCleared($this->tenantId);

            Log::shouldHaveReceived('info')
                ->once()
                ->with('Tenant context cleared', \Mockery::on(function ($context) {
                    return $context['previous_tenant_id'] === 123 &&
                           $context['session_id'] === 'test-session-id';
                }));
        });

        it('does not log when no previous tenant', function () {
            $this->logger->logContextCleared();

            Log::shouldNotHaveReceived('info');
        });
    });

    describe('logInvalidContextReset method', function () {
        it('logs invalid context reset with reset tenant', function () {
            $user = User::factory()->make([
                'id' => 456,
                'email' => 'test@example.com',
            ]);
            
            $invalidTenantId = TenantId::from(999);
            $resetToTenantId = TenantId::from(123);

            $this->logger->logInvalidContextReset($user, $invalidTenantId, $resetToTenantId);

            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Invalid tenant context reset', \Mockery::on(function ($context) {
                    return $context['user_id'] === 456 &&
                           $context['invalid_tenant_id'] === 999 &&
                           $context['reset_to_tenant_id'] === 123;
                }));
        });

        it('logs invalid context reset without reset tenant', function () {
            $user = User::factory()->make([
                'id' => 456,
                'email' => 'test@example.com',
            ]);
            
            $invalidTenantId = TenantId::from(999);

            $this->logger->logInvalidContextReset($user, $invalidTenantId);

            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Invalid tenant context reset', \Mockery::on(function ($context) {
                    return $context['reset_to_tenant_id'] === null;
                }));
        });
    });
});