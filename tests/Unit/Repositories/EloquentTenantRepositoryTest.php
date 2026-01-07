<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Repositories\Eloquent\EloquentTenantRepository;
use App\ValueObjects\TenantId;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('EloquentTenantRepository', function () {
    beforeEach(function () {
        $this->repository = new EloquentTenantRepository();
        $this->tenantId = TenantId::from(123);
    });

    describe('exists method', function () {
        it('returns true when organization exists', function () {
            Organization::factory()->create(['id' => 123]);

            expect($this->repository->exists($this->tenantId))->toBeTrue();
        });

        it('returns false when organization does not exist', function () {
            expect($this->repository->exists($this->tenantId))->toBeFalse();
        });
    });

    describe('find method', function () {
        it('returns organization when it exists', function () {
            $organization = Organization::factory()->create(['id' => 123, 'name' => 'Test Org']);

            $result = $this->repository->find($this->tenantId);

            expect($result)->not->toBeNull();
            expect($result->id)->toBe(123);
            expect($result->name)->toBe('Test Org');
        });

        it('returns null when organization does not exist', function () {
            $result = $this->repository->find($this->tenantId);

            expect($result)->toBeNull();
        });
    });

    describe('getName method', function () {
        it('returns organization name when it exists', function () {
            Organization::factory()->create(['id' => 123, 'name' => 'Test Organization']);

            $result = $this->repository->getName($this->tenantId);

            expect($result)->toBe('Test Organization');
        });

        it('returns null when organization does not exist', function () {
            $result = $this->repository->getName($this->tenantId);

            expect($result)->toBeNull();
        });
    });
});