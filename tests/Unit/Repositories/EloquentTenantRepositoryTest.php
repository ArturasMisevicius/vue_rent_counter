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

    describe('getName method', function () {
        it('returns organization name when it exists', function () {
            Organization::factory()->create(['id' => 123, 'name' => 'Test Organization']);

            $result = $this->repository->getName($this->tenantId);

            expect($result)->toBe('Test Organization');
        });

        it('returns fallback name when organization does not exist', function () {
            $result = $this->repository->getName($this->tenantId);

            expect($result)->toBe('Unknown Organization');
        });
    });
});
