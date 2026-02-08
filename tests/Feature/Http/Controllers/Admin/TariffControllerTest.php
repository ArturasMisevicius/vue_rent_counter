<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\Group;

/**
 * TariffControllerTest
 * 
 * Feature tests for TariffController CRUD operations.
 * 
 * Coverage:
 * - Authorization (admin-only access)
 * - Index with sorting and pagination
 * - Create and store operations
 * - Show with version history
 * - Edit and update operations
 * - Version creation workflow
 * - Delete operations
 * - Audit logging
 * 
 * Requirements:
 * - 2.1: Store tariff configuration as JSON
 * - 2.2: Validate time-of-use zones
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.2: Admin has full CRUD operations on tariffs
 * 
 * @package Tests\Feature\Http\Controllers\Admin
 */
#[Group('controllers')]
#[Group('tariffs')]
#[Group('admin')]
class TariffControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $tenant;
    private Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $this->manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $this->tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);

        // Create a provider for tariffs (providers are global, not tenant-scoped)
        $this->provider = Provider::factory()->create();
    }

    /**
     * Test: Admin can view tariff index.
     * 
     * Requirements: 11.1, 11.2
     */
    public function test_admin_can_view_tariff_index(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.tariffs.index'));

        $response->assertOk();
        $response->assertViewIs('admin.tariffs.index');
        $response->assertViewHas('tariffs');
    }

    /**
     * Test: Manager cannot access admin tariff routes.
     * 
     * Admin routes are restricted to admin role only.
     * Managers can view tariffs through Filament or API endpoints.
     * 
     * Requirements: 11.1, 11.3
     */
    public function test_manager_cannot_access_admin_tariff_routes(): void
    {
        $this->actingAs($this->manager);

        $response = $this->get(route('admin.tariffs.index'));

        $response->assertForbidden();
    }

    /**
     * Test: Tenant cannot access admin tariff routes.
     * 
     * Admin routes are restricted to admin role only.
     * Tenants can view tariffs through their own interface.
     * 
     * Requirements: 11.1, 11.4
     */
    public function test_tenant_cannot_access_admin_tariff_routes(): void
    {
        $this->actingAs($this->tenant);

        $response = $this->get(route('admin.tariffs.index'));

        $response->assertForbidden();
    }

    /**
     * Test: Index supports sorting by allowed columns.
     * 
     * Requirements: 11.1
     */
    public function test_index_supports_sorting(): void
    {
        $this->actingAs($this->admin);

        // Create tariffs with different dates
        Tariff::factory()->create([
            'provider_id' => $this->provider->id,
            'name' => 'Tariff A',
            'active_from' => '2025-01-01',
        ]);
        Tariff::factory()->create([
            'provider_id' => $this->provider->id,
            'name' => 'Tariff B',
            'active_from' => '2025-02-01',
        ]);

        // Test sorting by name
        $response = $this->get(route('admin.tariffs.index', ['sort' => 'name', 'direction' => 'asc']));
        $response->assertOk();

        // Test sorting by active_from
        $response = $this->get(route('admin.tariffs.index', ['sort' => 'active_from', 'direction' => 'desc']));
        $response->assertOk();
    }

    /**
     * Test: Index prevents SQL injection via sort parameter.
     * 
     * Requirements: 11.1
     */
    public function test_index_prevents_sql_injection_in_sort(): void
    {
        $this->actingAs($this->admin);

        // Create a tariff to verify table exists after injection attempt
        Tariff::factory()->create(['provider_id' => $this->provider->id]);

        // Attempt SQL injection via sort parameter
        $response = $this->get(route('admin.tariffs.index', ['sort' => 'id; DROP TABLE tariffs;--']));

        $response->assertOk(); // Should fallback to default sort
        
        // Verify table still exists and has data
        $this->assertDatabaseCount('tariffs', 1);
    }

    /**
     * Test: Admin can view create form.
     * 
     * Requirements: 11.1, 11.2
     */
    public function test_admin_can_view_create_form(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.tariffs.create'));

        $response->assertOk();
        $response->assertViewIs('admin.tariffs.create');
        $response->assertViewHas('providers');
    }

    /**
     * Test: Manager cannot view create form.
     * 
     * Requirements: 11.1, 11.3
     */
    public function test_manager_cannot_view_create_form(): void
    {
        $this->actingAs($this->manager);

        $response = $this->get(route('admin.tariffs.create'));

        $response->assertForbidden();
    }

    /**
     * Test: Admin can create flat rate tariff.
     * 
     * Requirements: 2.1, 11.1, 11.2
     */
    public function test_admin_can_create_flat_rate_tariff(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.tariffs.store'), [
            'provider_id' => $this->provider->id,
            'name' => 'Standard Electricity Rate',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.20,
            ],
            'active_from' => '2025-01-01',
            'active_until' => '2025-12-31',
        ]);

        $response->assertRedirect(route('admin.tariffs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tariffs', [
            'provider_id' => $this->provider->id,
            'name' => 'Standard Electricity Rate',
        ]);
    }

    /**
     * Test: Admin can create time-of-use tariff.
     * 
     * Requirements: 2.1, 2.2, 11.1, 11.2
     */
    public function test_admin_can_create_time_of_use_tariff(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.tariffs.store'), [
            'provider_id' => $this->provider->id,
            'name' => 'Day/Night Electricity Rate',
            'configuration' => [
                'type' => 'time_of_use',
                'currency' => 'EUR',
                'zones' => [
                    ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.25],
                    ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.15],
                ],
                'weekend_logic' => 'apply_night_rate',
            ],
            'active_from' => '2025-01-01',
        ]);

        $response->assertRedirect(route('admin.tariffs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tariffs', [
            'provider_id' => $this->provider->id,
            'name' => 'Day/Night Electricity Rate',
        ]);
    }

    /**
     * Test: Manager cannot create tariff.
     * 
     * Requirements: 11.1, 11.3
     */
    public function test_manager_cannot_create_tariff(): void
    {
        $this->actingAs($this->manager);

        $response = $this->post(route('admin.tariffs.store'), [
            'provider_id' => $this->provider->id,
            'name' => 'Test Tariff',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.20,
            ],
            'active_from' => '2025-01-01',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test: Admin can view tariff details.
     * 
     * Requirements: 11.1, 11.2
     */
    public function test_admin_can_view_tariff_details(): void
    {
        $this->actingAs($this->admin);

        $tariff = Tariff::factory()->create(['provider_id' => $this->provider->id]);

        $response = $this->get(route('admin.tariffs.show', $tariff));

        $response->assertOk();
        $response->assertViewIs('admin.tariffs.show');
        $response->assertViewHas('tariff');
        $response->assertViewHas('versionHistory');
    }

    /**
     * Test: Show displays version history.
     * 
     * Requirements: 11.1
     */
    public function test_show_displays_version_history(): void
    {
        $this->actingAs($this->admin);

        // Create multiple versions of the same tariff
        $tariff1 = Tariff::factory()->create([
            'provider_id' => $this->provider->id,
            'name' => 'Standard Rate',
            'active_from' => '2024-01-01',
            'active_until' => '2024-12-31',
        ]);

        $tariff2 = Tariff::factory()->create([
            'provider_id' => $this->provider->id,
            'name' => 'Standard Rate',
            'active_from' => '2025-01-01',
        ]);

        $response = $this->get(route('admin.tariffs.show', $tariff2));

        $response->assertOk();
        $response->assertViewHas('versionHistory', function ($history) use ($tariff1) {
            return $history->contains($tariff1);
        });
    }

    /**
     * Test: Admin can view edit form.
     * 
     * Requirements: 11.1, 11.2
     */
    public function test_admin_can_view_edit_form(): void
    {
        $this->actingAs($this->admin);

        $tariff = Tariff::factory()->create(['provider_id' => $this->provider->id]);

        $response = $this->get(route('admin.tariffs.edit', $tariff));

        $response->assertOk();
        $response->assertViewIs('admin.tariffs.edit');
        $response->assertViewHas('tariff');
        $response->assertViewHas('providers');
    }

    /**
     * Test: Manager cannot view edit form.
     * 
     * Requirements: 11.1, 11.3
     */
    public function test_manager_cannot_view_edit_form(): void
    {
        $this->actingAs($this->manager);

        $tariff = Tariff::factory()->create(['provider_id' => $this->provider->id]);

        $response = $this->get(route('admin.tariffs.edit', $tariff));

        $response->assertForbidden();
    }

    /**
     * Test: Admin can update tariff directly.
     * 
     * Requirements: 2.1, 11.1, 11.2
     */
    public function test_admin_can_update_tariff_directly(): void
    {
        $this->actingAs($this->admin);

        $tariff = Tariff::factory()->create([
            'provider_id' => $this->provider->id,
            'name' => 'Original Name',
        ]);

        $response = $this->put(route('admin.tariffs.update', $tariff), [
            'provider_id' => $this->provider->id,
            'name' => 'Updated Name',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.25,
            ],
            'active_from' => '2025-01-01',
        ]);

        $response->assertRedirect(route('admin.tariffs.show', $tariff));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tariffs', [
            'id' => $tariff->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test: Admin can create new tariff version.
     * 
     * Requirements: 2.1, 11.1, 11.2
     */
    public function test_admin_can_create_new_tariff_version(): void
    {
        $this->actingAs($this->admin);

        $oldTariff = Tariff::factory()->create([
            'provider_id' => $this->provider->id,
            'name' => 'Standard Rate',
            'active_from' => '2024-01-01',
            'active_until' => null,
        ]);

        $response = $this->put(route('admin.tariffs.update', $oldTariff), [
            'provider_id' => $this->provider->id,
            'name' => 'Standard Rate',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.30,
            ],
            'active_from' => '2025-01-01',
            'create_new_version' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Old tariff should have end date set
        $oldTariff->refresh();
        $this->assertEquals('2024-12-31', $oldTariff->active_until->format('Y-m-d'));

        // New tariff should exist (check count instead of exact match due to timestamp formatting)
        $this->assertDatabaseCount('tariffs', 2);
        
        // Verify new tariff has correct attributes
        $newTariff = Tariff::where('provider_id', $this->provider->id)
            ->where('name', 'Standard Rate')
            ->where('id', '!=', $oldTariff->id)
            ->first();
            
        $this->assertNotNull($newTariff);
        $this->assertEquals('2025-01-01', $newTariff->active_from->format('Y-m-d'));
    }

    /**
     * Test: Manager cannot update tariff.
     * 
     * Requirements: 11.1, 11.3
     */
    public function test_manager_cannot_update_tariff(): void
    {
        $this->actingAs($this->manager);

        $tariff = Tariff::factory()->create(['provider_id' => $this->provider->id]);

        $response = $this->put(route('admin.tariffs.update', $tariff), [
            'provider_id' => $this->provider->id,
            'name' => 'Updated Name',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.25,
            ],
            'active_from' => '2025-01-01',
        ]);

        $response->assertForbidden();
    }

    /**
     * Test: Admin can delete tariff.
     * 
     * Requirements: 11.1, 11.2
     */
    public function test_admin_can_delete_tariff(): void
    {
        $this->actingAs($this->admin);

        $tariff = Tariff::factory()->create(['provider_id' => $this->provider->id]);

        $response = $this->delete(route('admin.tariffs.destroy', $tariff));

        $response->assertRedirect(route('admin.tariffs.index'));
        $response->assertSessionHas('success');

        // Tariff model doesn't use soft deletes, so verify hard deletion
        $this->assertDatabaseMissing('tariffs', ['id' => $tariff->id]);
    }

    /**
     * Test: Manager cannot delete tariff.
     * 
     * Requirements: 11.1, 11.3
     */
    public function test_manager_cannot_delete_tariff(): void
    {
        $this->actingAs($this->manager);

        $tariff = Tariff::factory()->create(['provider_id' => $this->provider->id]);

        $response = $this->delete(route('admin.tariffs.destroy', $tariff));

        $response->assertForbidden();

        // Verify tariff still exists (not deleted)
        $this->assertDatabaseHas('tariffs', ['id' => $tariff->id]);
    }

    /**
     * Test: Tariff create operation is logged for audit trail.
     * 
     * Requirements: 11.1
     */
    public function test_tariff_create_is_logged(): void
    {
        Log::spy();
        
        $this->actingAs($this->admin);

        $this->post(route('admin.tariffs.store'), [
            'provider_id' => $this->provider->id,
            'name' => 'Test Tariff',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.20,
            ],
            'active_from' => '2025-01-01',
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Tariff created', \Mockery::on(function ($context) {
                return isset($context['user_id'], $context['tariff_id'], $context['provider_id'], $context['name'], $context['type']);
            }));
    }

    /**
     * Test: Tariff update operation is logged for audit trail.
     * 
     * Requirements: 11.1
     */
    public function test_tariff_update_is_logged(): void
    {
        Log::spy();
        
        $this->actingAs($this->admin);

        $tariff = Tariff::factory()->create(['provider_id' => $this->provider->id]);

        $this->put(route('admin.tariffs.update', $tariff), [
            'provider_id' => $this->provider->id,
            'name' => 'Updated Name',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.25,
            ],
            'active_from' => '2025-01-01',
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Tariff updated', \Mockery::on(function ($context) use ($tariff) {
                return $context['tariff_id'] === $tariff->id
                    && isset($context['user_id'], $context['provider_id'], $context['name']);
            }));
    }

    /**
     * Test: Tariff version creation is logged for audit trail.
     * 
     * Requirements: 11.1
     */
    public function test_tariff_version_creation_is_logged(): void
    {
        Log::spy();
        
        $this->actingAs($this->admin);

        $oldTariff = Tariff::factory()->create([
            'provider_id' => $this->provider->id,
            'name' => 'Standard Rate',
            'active_from' => '2024-01-01',
        ]);

        $this->put(route('admin.tariffs.update', $oldTariff), [
            'provider_id' => $this->provider->id,
            'name' => 'Standard Rate',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.30,
            ],
            'active_from' => '2025-01-01',
            'create_new_version' => true,
        ]);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Tariff version created', \Mockery::on(function ($context) use ($oldTariff) {
                return $context['old_tariff_id'] === $oldTariff->id
                    && isset($context['new_tariff_id'], $context['user_id'], $context['provider_id'], $context['name']);
            }));
    }

    /**
     * Test: Tariff delete operation is logged for audit trail.
     * 
     * Requirements: 11.1
     */
    public function test_tariff_delete_is_logged(): void
    {
        Log::spy();
        
        $this->actingAs($this->admin);

        $tariff = Tariff::factory()->create(['provider_id' => $this->provider->id]);

        $this->delete(route('admin.tariffs.destroy', $tariff));

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Tariff deleted', \Mockery::on(function ($context) use ($tariff) {
                return $context['tariff_id'] === $tariff->id
                    && isset($context['user_id'], $context['provider_id'], $context['name']);
            }));
    }
}
