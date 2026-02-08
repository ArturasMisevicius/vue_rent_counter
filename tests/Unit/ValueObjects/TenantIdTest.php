<?php

declare(strict_types=1);

use App\ValueObjects\TenantId;

describe('TenantId Value Object', function () {
    it('creates valid tenant ID from positive integer', function () {
        $tenantId = TenantId::from(123);
        
        expect($tenantId->getValue())->toBe(123);
        expect((string) $tenantId)->toBe('123');
    });

    it('throws exception for zero tenant ID', function () {
        expect(fn () => TenantId::from(0))
            ->toThrow(InvalidArgumentException::class, 'Tenant ID must be a positive integer, got: 0');
    });

    it('throws exception for negative tenant ID', function () {
        expect(fn () => TenantId::from(-1))
            ->toThrow(InvalidArgumentException::class, 'Tenant ID must be a positive integer, got: -1');
    });

    it('compares tenant IDs correctly', function () {
        $tenantId1 = TenantId::from(123);
        $tenantId2 = TenantId::from(123);
        $tenantId3 = TenantId::from(456);

        expect($tenantId1->equals($tenantId2))->toBeTrue();
        expect($tenantId1->equals($tenantId3))->toBeFalse();
    });

    it('converts to string correctly', function () {
        $tenantId = TenantId::from(789);
        
        expect($tenantId->__toString())->toBe('789');
        expect((string) $tenantId)->toBe('789');
    });
});