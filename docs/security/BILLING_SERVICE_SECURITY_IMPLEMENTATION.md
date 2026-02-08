# BillingService Security Implementation Guide

**Date**: 2025-11-25  
**Status**: 🔴 IMPLEMENTATION REQUIRED  
**Priority**: P0 - Critical

## Overview

This document provides step-by-step implementation instructions for securing the BillingService based on the security audit findings.

## Phase 1: Critical Fixes (Immediate)

### 1. Add Authorization Checks

**File**: `app/Services/BillingService.php`

**Location**: Beginning of `generateInvoice()` method (after line 54)

**Add**:
```php
// Authorization check
Gate::authorize('generateInvoice', [Tenant::class, $tenant]);

// Multi-tenancy validation
if (TenantContext::has() && TenantContext::id() !== $tenant->tenant_id) {
    throw new BillingException(__('billing.errors.cross_tenant_access_denied'));
}

// Verify tenant is active
if ($tenant->trashed()) {
    throw new BillingException(__('billing.errors.tenant_inactive'));
}
```

**Import Required**:
```php
use Illuminate\Support\Facades\Gate;
use App\Services\TenantContext;
```

---

### 2. Add Rate Limiting

**File**: `app/Services/BillingService.php`

**Add constants** (after class declaration):
```php
private const RATE_LIMIT_PER_USER = 10;
private const RATE_LIMIT_PER_TENANT = 100;
```

**Add method** (before generateInvoice):
```php
/**
 * Check rate limits for invoice generation.
 * 
 * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
 */
private function checkRateLimits(Tenant $tenant): void
{
    $user = auth()->user();
    
    if (!$user) {
        throw new BillingException(__('billing.errors.authentication_required'));
    }

    // Per-user rate limit
    $userKey = 'invoice_generation:user:' . $user->id;
    $userAttempts = RateLimiter::attempts($userKey);
    
    if ($userAttempts >= self::RATE_LIMIT_PER_USER) {
        throw new ThrottleRequestsException(__('billing.errors.rate_limit_user'));
    }
    
    RateLimiter::hit($userKey, 3600); // 1 hour

    // Per-tenant rate limit
    $tenantKey = 'invoice_generation:tenant:' . $tenant->tenant_id;
    $tenantAttempts = RateLimiter::attempts($tenantKey);
    
    if ($tenantAttempts >= self::RATE_LIMIT_PER_TENANT) {
        throw new ThrottleRequestsException(__('billing.errors.rate_limit_tenant'));
    }
    
    RateLimiter::hit($tenantKey, 3600); // 1 hour
}
```

**Import Required**:
```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
```

**Call in generateInvoice** (after authorization):
```php
$this->checkRateLimits($tenant);
```

---

### 3. Sanitize Logging

**File**: `app/Services/BillingService.php`

**Replace all log calls** with hashed IDs:

**Before**:
```php
$this->log('info', 'Starting invoice generation', [
    'tenant_id' => $tenant->id,
    'period_start' => $periodStart->toDateString(),
    'period_end' => $periodEnd->toDateString(),
]);
```

**After**:
```php
$this->log('info', 'Starting invoice generation', [
    'tenant_hash' => hash('sha256', (string) $tenant->id),
    'period_start' => $periodStart->toDateString(),
    'period_end' => $periodEnd->toDateString(),
]);
```

**Apply to all log statements** at lines:
- 59-62
- 88-91
- 102-106
- 136-140
- 146-149

---

### 4. Add Audit Trail

**File**: `app/Services/BillingService.php`

**Add method** (after generateInvoice):
```php
/**
 * Create audit record for invoice generation.
 */
private function auditInvoiceGeneration(
    Invoice $invoice,
    Tenant $tenant,
    BillingPeriod $period,
    float $executionTime,
    int $queryCount
): void {
    InvoiceGenerationAudit::create([
        'invoice_id' => $invoice->id,
        'tenant_id' => $tenant->tenant_id,
        'user_id' => auth()->id(),
        'period_start' => $period->start,
        'period_end' => $period->end,
        'total_amount' => $invoice->total_amount,
        'items_count' => $invoice->items()->count(),
        'metadata' => [
            'meters_processed' => $invoice->items()->distinct('meter_id')->count(),
            'hot water circulation_included' => $invoice->items()->where('description', 'like', '%hot water circulation%')->exists(),
        ],
        'execution_time_ms' => $executionTime,
        'query_count' => $queryCount,
    ]);
}
```

**Import Required**:
```php
use App\Models\InvoiceGenerationAudit;
```

**Call before return** in generateInvoice:
```php
// Track execution time
$startTime = microtime(true);
$startQueries = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;

// ... existing code ...

// Audit trail
$executionTime = (microtime(true) - $startTime) * 1000;
$queryCount = DB::getQueryLog() ? count(DB::getQueryLog()) - $startQueries : 0;
$this->auditInvoiceGeneration($invoice, $tenant, $billingPeriod, $executionTime, $queryCount);
```

---

## Phase 2: High Priority Fixes

### 5. Input Validation

**File**: Controllers that call BillingService

**Replace direct service calls** with FormRequest validation:

**Before**:
```php
public function generate(Request $request)
{
    $tenant = Tenant::find($request->tenant_id);
    $invoice = $billingService->generateInvoice($tenant, $start, $end);
}
```

**After**:
```php
public function generate(GenerateInvoiceRequest $request)
{
    $tenant = Tenant::find($request->validated('tenant_id'));
    $start = Carbon::parse($request->validated('period_start'));
    $end = Carbon::parse($request->validated('period_end'));
    
    $invoice = $billingService->generateInvoice($tenant, $start, $end);
}
```

---

### 6. Duplicate Invoice Prevention

**File**: `app/Services/BillingService.php`

**Add method**:
```php
/**
 * Check for duplicate invoices.
 * 
 * @throws BillingException If duplicate exists
 */
private function checkDuplicateInvoice(Tenant $tenant, BillingPeriod $period): void
{
    $existing = Invoice::where('tenant_renter_id', $tenant->id)
        ->where('billing_period_start', $period->start->toDateString())
        ->where('billing_period_end', $period->end->toDateString())
        ->whereIn('status', ['draft', 'finalized', 'paid'])
        ->exists();

    if ($existing) {
        throw new BillingException(__('billing.errors.duplicate_invoice'));
    }
}
```

**Call in generateInvoice** (after rate limiting):
```php
$this->checkDuplicateInvoice($tenant, $billingPeriod);
```

---

### 7. Calculation Validation

**File**: `app/Services/BillingService.php`

**Add method**:
```php
/**
 * Validate consumption value.
 * 
 * @throws BillingException If invalid
 */
private function validateConsumption(float $consumption, Meter $meter): void
{
    if ($consumption < 0) {
        throw new BillingException(__('billing.errors.negative_consumption', [
            'meter' => $meter->serial_number,
        ]));
    }

    // Maximum reasonable consumption per meter type
    $maxConsumption = match ($meter->type) {
        MeterType::ELECTRICITY => 10000, // 10,000 kWh
        MeterType::WATER_COLD, MeterType::WATER_HOT => 1000, // 1,000 m³
        MeterType::HEATING => 50000, // 50,000 kWh
    };

    if ($consumption > $maxConsumption) {
        $this->log('warning', 'Excessive consumption detected', [
            'meter_hash' => hash('sha256', (string) $meter->id),
            'consumption' => $consumption,
            'max_allowed' => $maxConsumption,
        ]);
    }
}
```

**Call in createInvoiceItemForZone** (after consumption calculation):
```php
$this->validateConsumption($consumption, $meter);
```

---

### 8. Generic Error Messages

**File**: `app/Services/BillingService.php`

**Replace exception messages**:

**Before**:
```php
throw new BillingException("Tenant {$tenant->id} has no associated property");
```

**After**:
```php
$this->log('error', 'Tenant has no property', [
    'tenant_hash' => hash('sha256', (string) $tenant->id),
]);
throw new BillingException(__('billing.errors.tenant_no_property'));
```

**Apply to all exception throws** at lines:
- 75-77
- 79-81
- 445-447

---

## Phase 3: Translation Keys

**File**: `lang/en/billing.php`

**Add**:
```php
return [
    'errors' => [
        'cross_tenant_access_denied' => 'You do not have permission to access this tenant.',
        'tenant_inactive' => 'The tenant account is inactive.',
        'authentication_required' => 'Authentication is required for this operation.',
        'rate_limit_user' => 'You have exceeded the invoice generation limit. Please try again later.',
        'rate_limit_tenant' => 'The tenant has exceeded the invoice generation limit. Please try again later.',
        'duplicate_invoice' => 'An invoice already exists for this period.',
        'tenant_no_property' => 'The tenant has no associated property.',
        'property_no_meters' => 'The property has no meters configured.',
        'provider_not_found' => 'No provider found for this service type.',
        'negative_consumption' => 'Invalid negative consumption detected for meter :meter.',
    ],
    
    'validation' => [
        'tenant_required' => 'Tenant is required.',
        'tenant_not_found' => 'Tenant not found.',
        'tenant_inactive' => 'Tenant is inactive.',
        'period_start_required' => 'Period start date is required.',
        'period_start_future' => 'Period start date cannot be in the future.',
        'period_end_required' => 'Period end date is required.',
        'period_end_future' => 'Period end date cannot be in the future.',
        'period_too_long' => 'Billing period cannot exceed 3 months.',
        'duplicate_invoice' => 'An invoice already exists for this period.',
    ],
    
    'fields' => [
        'tenant' => 'Tenant',
        'period_start' => 'Period Start',
        'period_end' => 'Period End',
    ],
];
```

**Replicate** for `lang/lt/billing.php` and `lang/ru/billing.php`.

---

## Phase 4: Testing

### Security Tests

**File**: `tests/Security/BillingServiceSecurityTest.php`

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(BillingService::class);
});

test('unauthorized user cannot generate invoice', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['role' => 'tenant']);
    
    $this->actingAs($user);
    
    expect(fn() => $this->service->generateInvoice(
        $tenant,
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth()
    ))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});

test('cross-tenant invoice generation is blocked', function () {
    $tenantA = Tenant::factory()->create(['tenant_id' => 1]);
    $tenantB = Tenant::factory()->create(['tenant_id' => 2]);
    $manager = User::factory()->create(['role' => 'manager', 'tenant_id' => 1]);
    
    $this->actingAs($manager);
    TenantContext::set(1);
    
    expect(fn() => $this->service->generateInvoice(
        $tenantB,
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth()
    ))->toThrow(\App\Exceptions\BillingException::class);
});

test('rate limiting prevents excessive invoice generation', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create(['role' => 'manager', 'tenant_id' => $tenant->tenant_id]);
    
    $this->actingAs($manager);
    TenantContext::set($tenant->tenant_id);
    
    // Generate 10 invoices (at limit)
    for ($i = 0; $i < 10; $i++) {
        $start = Carbon::now()->subMonths($i)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $this->service->generateInvoice($tenant, $start, $end);
    }
    
    // 11th should be rate limited
    expect(fn() => $this->service->generateInvoice(
        $tenant,
        Carbon::now()->subMonths(11)->startOfMonth(),
        Carbon::now()->subMonths(11)->endOfMonth()
    ))->toThrow(\Illuminate\Http\Exceptions\ThrottleRequestsException::class);
});

test('duplicate invoice generation is prevented', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create(['role' => 'manager', 'tenant_id' => $tenant->tenant_id]);
    
    $this->actingAs($manager);
    TenantContext::set($tenant->tenant_id);
    
    $start = Carbon::now()->startOfMonth();
    $end = Carbon::now()->endOfMonth();
    
    // First generation succeeds
    $invoice1 = $this->service->generateInvoice($tenant, $start, $end);
    expect($invoice1)->toBeInstanceOf(\App\Models\Invoice::class);
    
    // Second generation fails
    expect(fn() => $this->service->generateInvoice($tenant, $start, $end))
        ->toThrow(\App\Exceptions\BillingException::class);
});

test('audit trail is created for invoice generation', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create(['role' => 'manager', 'tenant_id' => $tenant->tenant_id]);
    
    $this->actingAs($manager);
    TenantContext::set($tenant->tenant_id);
    
    $invoice = $this->service->generateInvoice(
        $tenant,
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth()
    );
    
    $this->assertDatabaseHas('invoice_generation_audits', [
        'invoice_id' => $invoice->id,
        'tenant_id' => $tenant->tenant_id,
        'user_id' => $manager->id,
    ]);
});

test('sensitive IDs are hashed in logs', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create(['role' => 'manager', 'tenant_id' => $tenant->tenant_id]);
    
    $this->actingAs($manager);
    TenantContext::set($tenant->tenant_id);
    
    Log::shouldReceive('info')
        ->once()
        ->with('Starting invoice generation', Mockery::on(function ($context) use ($tenant) {
            return isset($context['tenant_hash']) 
                && $context['tenant_hash'] === hash('sha256', (string) $tenant->id)
                && !isset($context['tenant_id']);
        }));
    
    $this->service->generateInvoice(
        $tenant,
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth()
    );
});
```

---

## Deployment Checklist

### Pre-Deployment

- [ ] Run migrations: `php artisan migrate`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Run tests: `php artisan test --filter=BillingServiceSecurityTest`
- [ ] Verify translations exist for all locales
- [ ] Review rate limit thresholds for production load
- [ ] Configure monitoring alerts

### Post-Deployment

- [ ] Monitor authorization failure logs
- [ ] Monitor rate limit hit metrics
- [ ] Verify audit trail completeness
- [ ] Check for cross-tenant access attempts
- [ ] Review invoice generation performance

---

## Monitoring

### Metrics to Track

1. **Authorization Failures**
   - Alert threshold: >10/minute
   - Action: Investigate potential attack

2. **Rate Limit Hits**
   - Alert threshold: >100/minute
   - Action: Review rate limit configuration

3. **Duplicate Invoice Attempts**
   - Alert threshold: >5/hour
   - Action: Investigate UI/workflow issues

4. **Cross-Tenant Access Attempts**
   - Alert threshold: >1/hour
   - Action: Immediate security review

5. **Audit Trail Gaps**
   - Alert threshold: Any missing audit records
   - Action: Investigate audit system

---

## Compliance

### GDPR

- ✅ PII redaction in logs (hashed IDs)
- ✅ Audit trail for data processing
- ✅ Purpose limitation (billing only)
- ✅ Data minimization (only necessary fields)

### SOX

- ✅ Segregation of duties (role-based access)
- ✅ Audit trail completeness
- ✅ Access controls enforced
- ✅ Financial calculation accuracy

### Security

- ✅ Authorization at service level
- ✅ Multi-tenancy enforcement
- ✅ Rate limiting active
- ✅ Input validation complete
- ✅ Error handling secure

---

**Document Version**: 1.0.0  
**Last Updated**: 2025-11-25  
**Status**: Ready for Implementation
