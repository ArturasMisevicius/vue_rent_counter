<?php

declare(strict_types=1);

use App\Contracts\TenantAuditLoggerInterface;
use App\Contracts\TenantAuthorizationServiceInterface;
use App\Enums\UserRole;
use App\Exceptions\UnauthorizedTenantSwitchException;
use App\Models\User;
use App\Repositories\TenantRepositoryInterface;
use App\Services\TenantContext;
use App\ValueObjects\TenantId;
use Illuminate\Contracts\Session\Session;

describe('TenantContext Service', function () {
    beforeEach(function () {
        $this->session = \Mockery::mock(Session::class);
        $this->tenantRepository = \Mockery::mock(TenantRepositoryInterface::class);
        $this->authorizationService = \Mockery::mock(TenantAuthorizationServiceInterface::class);
        $this->auditLogger = \Mockery::mock(TenantAuditLoggerInterface::class);
        $this->tenantContext = new TenantContext(
            $this->session,
            $this->tenantRepository,
            $this->authorizationService,
            $this->auditLogger
        );
    });

    afterEach(function () {
        \Mockery::close();
    });

    describe('set method', function () {
        it('sets tenant context when tenant exists', function () {
            $tenantId = TenantId::from(123);
            
            $this->tenantRepository
                ->shouldReceive('exists')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123))
                ->once()
                ->andReturn(true);

            $this->session
                ->shouldReceive('put')
                ->with('tenant_context', 123)
                ->once();

            $this->auditLogger
                ->shouldReceive('logContextSet')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123))
                ->once();

            $this->tenantContext->set(123);
        });

        it('throws exception when tenant does not exist', function () {
            $this->tenantRepository
                ->shouldReceive('exists')
                ->once()
                ->andReturn(false);

            expect(fn () => $this->tenantContext->set(123))
                ->toThrow(InvalidArgumentException::class, 'Organization with ID 123 does not exist');
        });

        it('throws exception for invalid tenant ID', function () {
            expect(fn () => $this->tenantContext->set(0))
                ->toThrow(InvalidArgumentException::class, 'Tenant ID must be a positive integer, got: 0');
        });
    });

    describe('get method', function () {
        it('returns tenant ID when set', function () {
            $this->session
                ->shouldReceive('get')
                ->with('tenant_context')
                ->once()
                ->andReturn(123);

            $result = $this->tenantContext->get();

            expect($result)->toBe(123);
        });

        it('returns null when not set', function () {
            $this->session
                ->shouldReceive('get')
                ->with('tenant_context')
                ->once()
                ->andReturn(null);

            $result = $this->tenantContext->get();

            expect($result)->toBeNull();
        });

        it('returns null for invalid tenant ID', function () {
            $this->session
                ->shouldReceive('get')
                ->with('tenant_context')
                ->once()
                ->andReturn(0);

            $result = $this->tenantContext->get();

            expect($result)->toBeNull();
        });
    });

    describe('switch method', function () {
        it('switches tenant context successfully', function () {
            $user = User::factory()->make(['id' => 456]);

            // Mock authorization service - canSwitchTo is called first
            $this->authorizationService
                ->shouldReceive('canSwitchTo')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123), $user)
                ->once()
                ->andReturn(true);

            // Mock session operations for get() call in switch method
            $this->session
                ->shouldReceive('get')
                ->with('tenant_context')
                ->once()
                ->andReturn(null);

            // Mock tenant repository for set() method call
            $this->tenantRepository
                ->shouldReceive('exists')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123))
                ->once()
                ->andReturn(true);

            // Mock session put for set() method call
            $this->session
                ->shouldReceive('put')
                ->with('tenant_context', 123)
                ->once();

            // Mock audit logger for set() method call
            $this->auditLogger
                ->shouldReceive('logContextSet')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123))
                ->once();

            // Mock tenant repository for getName() call
            $this->tenantRepository
                ->shouldReceive('getName')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123))
                ->once()
                ->andReturn('Test Org');

            // Mock audit logger for switch logging
            $this->auditLogger
                ->shouldReceive('logContextSwitch')
                ->with($user, Mockery::on(fn ($arg) => $arg->getValue() === 123), null, 'Test Org')
                ->once();

            $this->tenantContext->switch(123, $user);
        });

        it('throws exception when user cannot switch to tenant', function () {
            $user = User::factory()->make(['id' => 456, 'role' => UserRole::ADMIN]);

            $this->authorizationService
                ->shouldReceive('canSwitchTo')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123), $user)
                ->once()
                ->andReturn(false);

            expect(fn () => $this->tenantContext->switch(123, $user))
                ->toThrow(UnauthorizedTenantSwitchException::class);
        });
    });

    describe('validate method', function () {
        it('returns false when no tenant context is set', function () {
            $user = User::factory()->make();

            $this->session
                ->shouldReceive('get')
                ->with('tenant_context')
                ->once()
                ->andReturn(null);

            $result = $this->tenantContext->validate($user);

            expect($result)->toBeFalse();
        });

        it('validates user access through authorization service', function () {
            $user = User::factory()->make();

            $this->session
                ->shouldReceive('get')
                ->with('tenant_context')
                ->once()
                ->andReturn(123);

            $this->authorizationService
                ->shouldReceive('canAccessTenant')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123), $user)
                ->once()
                ->andReturn(true);

            $result = $this->tenantContext->validate($user);

            expect($result)->toBeTrue();
        });
    });

    describe('clear method', function () {
        it('clears tenant context and logs', function () {
            $this->session
                ->shouldReceive('get')
                ->with('tenant_context')
                ->once()
                ->andReturn(123);

            $this->session
                ->shouldReceive('forget')
                ->with('tenant_context')
                ->once();

            $this->auditLogger
                ->shouldReceive('logContextCleared')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123))
                ->once();

            $this->tenantContext->clear();
        });

        it('does not log when no previous context', function () {
            $this->session
                ->shouldReceive('get')
                ->with('tenant_context')
                ->once()
                ->andReturn(null);

            $this->session
                ->shouldReceive('forget')
                ->with('tenant_context')
                ->once();

            $this->auditLogger
                ->shouldReceive('logContextCleared')
                ->with(null)
                ->once();

            $this->tenantContext->clear();
        });
    });

    describe('getDefaultTenant method', function () {
        it('returns default tenant from authorization service', function () {
            $user = User::factory()->make();
            $tenantId = TenantId::from(123);

            $this->authorizationService
                ->shouldReceive('getDefaultTenant')
                ->with($user)
                ->once()
                ->andReturn($tenantId);

            $result = $this->tenantContext->getDefaultTenant($user);

            expect($result)->toBe(123);
        });

        it('returns null when no default tenant', function () {
            $user = User::factory()->make();

            $this->authorizationService
                ->shouldReceive('getDefaultTenant')
                ->with($user)
                ->once()
                ->andReturn(null);

            $result = $this->tenantContext->getDefaultTenant($user);

            expect($result)->toBeNull();
        });
    });

    describe('initialize method', function () {
        it('sets default tenant when no context is set', function () {
            $user = User::factory()->make();

            // No current context
            $this->session
                ->shouldReceive('get')
                ->with('tenant_context')
                ->once()
                ->andReturn(null);

            // Has default tenant
            $this->authorizationService
                ->shouldReceive('getDefaultTenant')
                ->with($user)
                ->once()
                ->andReturn(TenantId::from(123));

            // Set the default tenant
            $this->tenantRepository
                ->shouldReceive('exists')
                ->once()
                ->andReturn(true);

            $this->session
                ->shouldReceive('put')
                ->with('tenant_context', 123)
                ->once();

            $this->auditLogger
                ->shouldReceive('logContextSet')
                ->once();

            $this->tenantContext->initialize($user);
        });

        it('validates and resets invalid context', function () {
            $user = User::factory()->make();

            // Has current context - called twice (once in initialize, once in validate)
            $this->session
                ->shouldReceive('get')
                ->with('tenant_context')
                ->times(3) // Once in initialize, once in validate, once in clear
                ->andReturn(123);

            // Context is invalid
            $this->authorizationService
                ->shouldReceive('canAccessTenant')
                ->once()
                ->andReturn(false);

            // Clear context
            $this->session
                ->shouldReceive('forget')
                ->with('tenant_context')
                ->once();

            $this->auditLogger
                ->shouldReceive('logContextCleared')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123))
                ->once();

            // Get default tenant
            $this->authorizationService
                ->shouldReceive('getDefaultTenant')
                ->once()
                ->andReturn(TenantId::from(456));

            // Set new default tenant
            $this->tenantRepository
                ->shouldReceive('exists')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 456))
                ->once()
                ->andReturn(true);

            $this->session
                ->shouldReceive('put')
                ->with('tenant_context', 456)
                ->once();

            $this->auditLogger
                ->shouldReceive('logContextSet')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 456))
                ->once();

            $this->auditLogger
                ->shouldReceive('logInvalidContextReset')
                ->with($user, Mockery::on(fn ($arg) => $arg->getValue() === 123), Mockery::on(fn ($arg) => $arg->getValue() === 456))
                ->once();

            $this->tenantContext->initialize($user);
        });
    });

    describe('canSwitchTo method', function () {
        it('delegates to authorization service', function () {
            $user = User::factory()->make();

            $this->authorizationService
                ->shouldReceive('canSwitchTo')
                ->with(Mockery::on(fn ($arg) => $arg->getValue() === 123), $user)
                ->once()
                ->andReturn(true);

            $result = $this->tenantContext->canSwitchTo(123, $user);

            expect($result)->toBeTrue();
        });

        it('returns false for invalid tenant ID', function () {
            $user = User::factory()->make();

            $result = $this->tenantContext->canSwitchTo(0, $user);

            expect($result)->toBeFalse();
        });
    });
});