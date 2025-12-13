<?php

use App\Enums\TenantStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\AuditAction;

// TenantStatus Tests
test('TenantStatus enum has correct values', function () {
    expect(TenantStatus::ACTIVE->value)->toBe('active')
        ->and(TenantStatus::SUSPENDED->value)->toBe('suspended')
        ->and(TenantStatus::PENDING->value)->toBe('pending')
        ->and(TenantStatus::CANCELLED->value)->toBe('cancelled');
});

test('TenantStatus enum provides labels', function () {
    expect(TenantStatus::ACTIVE->getLabel())->toBeString()
        ->and(TenantStatus::SUSPENDED->getLabel())->toBeString()
        ->and(TenantStatus::PENDING->getLabel())->toBeString()
        ->and(TenantStatus::CANCELLED->getLabel())->toBeString();
});

test('TenantStatus enum provides colors', function () {
    expect(TenantStatus::ACTIVE->getColor())->toBe('success')
        ->and(TenantStatus::SUSPENDED->getColor())->toBe('warning')
        ->and(TenantStatus::PENDING->getColor())->toBe('info')
        ->and(TenantStatus::CANCELLED->getColor())->toBe('danger');
});

test('TenantStatus enum provides icons', function () {
    expect(TenantStatus::ACTIVE->getIcon())->toBe('heroicon-o-check-circle')
        ->and(TenantStatus::SUSPENDED->getIcon())->toBe('heroicon-o-pause-circle')
        ->and(TenantStatus::PENDING->getIcon())->toBe('heroicon-o-clock')
        ->and(TenantStatus::CANCELLED->getIcon())->toBe('heroicon-o-x-circle');
});

test('TenantStatus enum validates state transitions', function () {
    // From PENDING
    expect(TenantStatus::PENDING->canTransitionTo(TenantStatus::ACTIVE))->toBeTrue()
        ->and(TenantStatus::PENDING->canTransitionTo(TenantStatus::CANCELLED))->toBeTrue()
        ->and(TenantStatus::PENDING->canTransitionTo(TenantStatus::SUSPENDED))->toBeFalse();
    
    // From ACTIVE
    expect(TenantStatus::ACTIVE->canTransitionTo(TenantStatus::SUSPENDED))->toBeTrue()
        ->and(TenantStatus::ACTIVE->canTransitionTo(TenantStatus::CANCELLED))->toBeTrue()
        ->and(TenantStatus::ACTIVE->canTransitionTo(TenantStatus::PENDING))->toBeFalse();
    
    // From SUSPENDED
    expect(TenantStatus::SUSPENDED->canTransitionTo(TenantStatus::ACTIVE))->toBeTrue()
        ->and(TenantStatus::SUSPENDED->canTransitionTo(TenantStatus::CANCELLED))->toBeTrue()
        ->and(TenantStatus::SUSPENDED->canTransitionTo(TenantStatus::PENDING))->toBeFalse();
    
    // From CANCELLED (no transitions allowed)
    expect(TenantStatus::CANCELLED->canTransitionTo(TenantStatus::ACTIVE))->toBeFalse()
        ->and(TenantStatus::CANCELLED->canTransitionTo(TenantStatus::SUSPENDED))->toBeFalse()
        ->and(TenantStatus::CANCELLED->canTransitionTo(TenantStatus::PENDING))->toBeFalse();
});

// SubscriptionPlan Tests
test('SubscriptionPlan enum has correct values', function () {
    expect(SubscriptionPlan::BASIC->value)->toBe('basic')
        ->and(SubscriptionPlan::PROFESSIONAL->value)->toBe('professional')
        ->and(SubscriptionPlan::ENTERPRISE->value)->toBe('enterprise')
        ->and(SubscriptionPlan::CUSTOM->value)->toBe('custom');
});

test('SubscriptionPlan enum provides labels', function () {
    expect(SubscriptionPlan::BASIC->getLabel())->toBeString()
        ->and(SubscriptionPlan::PROFESSIONAL->getLabel())->toBeString()
        ->and(SubscriptionPlan::ENTERPRISE->getLabel())->toBeString()
        ->and(SubscriptionPlan::CUSTOM->getLabel())->toBeString();
});

test('SubscriptionPlan enum provides colors', function () {
    expect(SubscriptionPlan::BASIC->getColor())->toBe('gray')
        ->and(SubscriptionPlan::PROFESSIONAL->getColor())->toBe('info')
        ->and(SubscriptionPlan::ENTERPRISE->getColor())->toBe('success')
        ->and(SubscriptionPlan::CUSTOM->getColor())->toBe('warning');
});

test('SubscriptionPlan enum provides resource limits', function () {
    expect(SubscriptionPlan::BASIC->getMaxProperties())->toBe(100)
        ->and(SubscriptionPlan::PROFESSIONAL->getMaxProperties())->toBe(500)
        ->and(SubscriptionPlan::ENTERPRISE->getMaxProperties())->toBe(9999)
        ->and(SubscriptionPlan::CUSTOM->getMaxProperties())->toBe(9999);
    
    expect(SubscriptionPlan::BASIC->getMaxUsers())->toBe(10)
        ->and(SubscriptionPlan::PROFESSIONAL->getMaxUsers())->toBe(50)
        ->and(SubscriptionPlan::ENTERPRISE->getMaxUsers())->toBe(999)
        ->and(SubscriptionPlan::CUSTOM->getMaxUsers())->toBe(999);
});

test('SubscriptionPlan enum provides pricing', function () {
    expect(SubscriptionPlan::BASIC->getMonthlyPrice())->toBe(29.99)
        ->and(SubscriptionPlan::PROFESSIONAL->getMonthlyPrice())->toBe(99.99)
        ->and(SubscriptionPlan::ENTERPRISE->getMonthlyPrice())->toBe(299.99)
        ->and(SubscriptionPlan::CUSTOM->getMonthlyPrice())->toBe(0.00);
});

test('SubscriptionPlan enum provides features', function () {
    $starterFeatures = SubscriptionPlan::BASIC->getFeatures();
    expect($starterFeatures)->toBeArray()
        ->and($starterFeatures)->toContain('basic_reporting')
        ->and($starterFeatures)->toContain('email_support');
    
    $professionalFeatures = SubscriptionPlan::PROFESSIONAL->getFeatures();
    expect($professionalFeatures)->toBeArray()
        ->and($professionalFeatures)->toContain('advanced_reporting')
        ->and($professionalFeatures)->toContain('api_access');
    
    $enterpriseFeatures = SubscriptionPlan::ENTERPRISE->getFeatures();
    expect($enterpriseFeatures)->toBeArray()
        ->and($enterpriseFeatures)->toContain('custom_reporting')
        ->and($enterpriseFeatures)->toContain('sso_integration');
    
    $customFeatures = SubscriptionPlan::CUSTOM->getFeatures();
    expect($customFeatures)->toBeArray()
        ->and($customFeatures)->toContain('all_features')
        ->and($customFeatures)->toContain('custom_development');
});

// AuditAction Tests
test('AuditAction enum has correct values', function () {
    expect(AuditAction::TENANT_CREATED->value)->toBe('tenant_created')
        ->and(AuditAction::TENANT_UPDATED->value)->toBe('tenant_updated')
        ->and(AuditAction::TENANT_SUSPENDED->value)->toBe('tenant_suspended')
        ->and(AuditAction::TENANT_DELETED->value)->toBe('tenant_deleted')
        ->and(AuditAction::USER_IMPERSONATED->value)->toBe('user_impersonated')
        ->and(AuditAction::BULK_OPERATION->value)->toBe('bulk_operation')
        ->and(AuditAction::SYSTEM_CONFIG_CHANGED->value)->toBe('system_config_changed');
});

test('AuditAction enum provides labels', function () {
    expect(AuditAction::TENANT_CREATED->getLabel())->toBeString()
        ->and(AuditAction::TENANT_UPDATED->getLabel())->toBeString()
        ->and(AuditAction::USER_IMPERSONATED->getLabel())->toBeString()
        ->and(AuditAction::SYSTEM_CONFIG_CHANGED->getLabel())->toBeString();
});

test('AuditAction enum provides colors', function () {
    expect(AuditAction::TENANT_CREATED->getColor())->toBe('success')
        ->and(AuditAction::TENANT_UPDATED->getColor())->toBe('info')
        ->and(AuditAction::TENANT_SUSPENDED->getColor())->toBe('warning')
        ->and(AuditAction::TENANT_DELETED->getColor())->toBe('danger')
        ->and(AuditAction::USER_IMPERSONATED->getColor())->toBe('warning')
        ->and(AuditAction::SYSTEM_CONFIG_CHANGED->getColor())->toBe('warning');
});

test('AuditAction enum provides icons', function () {
    expect(AuditAction::TENANT_CREATED->getIcon())->toBe('heroicon-o-plus-circle')
        ->and(AuditAction::TENANT_UPDATED->getIcon())->toBe('heroicon-o-pencil-square')
        ->and(AuditAction::TENANT_DELETED->getIcon())->toBe('heroicon-o-trash')
        ->and(AuditAction::USER_IMPERSONATED->getIcon())->toBe('heroicon-o-user-circle')
        ->and(AuditAction::SYSTEM_CONFIG_CHANGED->getIcon())->toBe('heroicon-o-cog-6-tooth');
});

test('AuditAction enum provides severity levels', function () {
    expect(AuditAction::TENANT_DELETED->getSeverity())->toBe('high')
        ->and(AuditAction::USER_SUSPENDED->getSeverity())->toBe('high')
        ->and(AuditAction::TENANT_SUSPENDED->getSeverity())->toBe('medium')
        ->and(AuditAction::USER_IMPERSONATED->getSeverity())->toBe('medium')
        ->and(AuditAction::SYSTEM_CONFIG_CHANGED->getSeverity())->toBe('medium')
        ->and(AuditAction::TENANT_CREATED->getSeverity())->toBe('low')
        ->and(AuditAction::TENANT_UPDATED->getSeverity())->toBe('low');
});
