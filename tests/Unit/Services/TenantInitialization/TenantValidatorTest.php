<?php

declare(strict_types=1);

namespace Tests\Unit\Services\TenantInitialization;

use App\Exceptions\TenantInitializationException;
use App\Models\Organization;
use App\Services\TenantInitialization\TenantValidator;
use Tests\TestCase;

final class TenantValidatorTest extends TestCase
{
    private TenantValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TenantValidator();
    }

    public function test_validates_existing_tenant_successfully(): void
    {
        $tenant = Organization::factory()->create();

        $this->validator->validate($tenant);

        // If no exception is thrown, validation passed
        $this->assertTrue(true);
    }

    public function test_throws_exception_for_non_persisted_tenant(): void
    {
        $tenant = Organization::factory()->make(); // Not saved to database

        $this->expectException(TenantInitializationException::class);
        $this->expectExceptionMessage('Tenant must be persisted to database');

        $this->validator->validate($tenant);
    }

    public function test_throws_exception_for_tenant_without_name(): void
    {
        $tenant = Organization::factory()->create();
        $tenant->name = '';

        $this->expectException(TenantInitializationException::class);
        $this->expectExceptionMessage('Tenant name is required');

        $this->validator->validate($tenant);
    }

    public function test_throws_exception_for_tenant_without_id(): void
    {
        $tenant = Organization::factory()->create();
        $tenant->exists = true; // Fake exists but no ID
        $tenant->id = null;

        $this->expectException(TenantInitializationException::class);
        $this->expectExceptionMessage('Tenant ID is required');

        $this->validator->validate($tenant);
    }

    public function test_validates_for_operation_with_required_fields(): void
    {
        $tenant = Organization::factory()->create([
            'email' => 'test@example.com',
            'phone' => '+1234567890',
        ]);

        $this->validator->validateForOperation($tenant, ['email', 'phone']);

        // If no exception is thrown, validation passed
        $this->assertTrue(true);
    }

    public function test_throws_exception_for_missing_required_field(): void
    {
        $tenant = Organization::factory()->create(['email' => null]);

        $this->expectException(TenantInitializationException::class);
        $this->expectExceptionMessage("Required field 'email' is missing or empty");

        $this->validator->validateForOperation($tenant, ['email']);
    }
}