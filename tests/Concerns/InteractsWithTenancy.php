<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for testing multi-tenancy features
 */
trait InteractsWithTenancy
{
    protected ?Tenant $tenant = null;
    protected ?Tenant $otherTenant = null;

    /**
     * Setup tenant context for testing
     */
    protected function setupTenantContext(): void
    {
        $this->tenant = Tenant::factory()->create();
        app(TenantContext::class)->set($this->tenant->id);
    }

    /**
     * Create a record for the current tenant
     */
    protected function createTenantRecord(string $model, array $attributes = []): Model
    {
        if (!$this->tenant) {
            $this->setupTenantContext();
        }

        return $model::factory()->create(array_merge(
            ['tenant_id' => $this->tenant->id],
            $attributes
        ));
    }

    /**
     * Create a record for another tenant
     */
    protected function createOtherTenantRecord(string $model, array $attributes = []): Model
    {
        if (!$this->otherTenant) {
            $this->otherTenant = Tenant::factory()->create();
        }

        return $model::factory()->create(array_merge(
            ['tenant_id' => $this->otherTenant->id],
            $attributes
        ));
    }

    /**
     * Assert that tenant isolation is enforced
     */
    protected function assertTenantIsolation(string $model): void
    {
        $ownRecord = $this->createTenantRecord($model);
        $otherRecord = $this->createOtherTenantRecord($model);

        $results = $model::all();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($ownRecord->id);
        expect($results->contains($otherRecord))->toBeFalse();
    }

    /**
     * Assert that cross-tenant access is prevented
     */
    protected function assertCrossTenantAccessPrevented(string $model, Model $record): void
    {
        // Switch to another tenant
        $originalTenant = $this->tenant;
        $this->setupOtherTenantContext();

        // Try to access the record
        $found = $model::find($record->id);

        expect($found)->toBeNull();

        // Restore original tenant
        if ($originalTenant) {
            app(TenantContext::class)->set($originalTenant->id);
        }
    }

    /**
     * Setup context for another tenant
     */
    protected function setupOtherTenantContext(): void
    {
        if (!$this->otherTenant) {
            $this->otherTenant = Tenant::factory()->create();
        }

        app(TenantContext::class)->set($this->otherTenant->id);
    }

    /**
     * Assert that tenant_id cannot be changed
     */
    protected function assertTenantIdImmutable(Model $record): void
    {
        $originalTenantId = $record->tenant_id;

        $record->update(['tenant_id' => 999]);

        expect($record->fresh()->tenant_id)->toBe($originalTenantId);
    }

    /**
     * Assert that hierarchical scope is applied
     */
    protected function assertHierarchicalScope(string $model, int $managerId): void
    {
        $this->actingAsManager();

        $building = \App\Models\Building::factory()->create([
            'manager_id' => $managerId,
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        $ownRecord = $model::factory()->create([
            'building_id' => $building->id,
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        $otherBuilding = \App\Models\Building::factory()->create([
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        $otherRecord = $model::factory()->create([
            'building_id' => $otherBuilding->id,
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        $results = $model::all();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($ownRecord->id);
    }
}
