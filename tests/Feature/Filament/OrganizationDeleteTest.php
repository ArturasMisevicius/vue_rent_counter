<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\SuperAdminAuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin user
        $this->superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        // Create test organization
        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function superadmin_can_see_delete_button_on_organization_table(): void
    {
        $this->actingAs($this->superadmin);

        Livewire::test(\App\Filament\Resources\OrganizationResource\Pages\ListOrganizations::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$this->organization])
            ->assertTableActionExists('delete');
    }

    /** @test */
    public function superadmin_can_see_delete_button_on_organization_view_page(): void
    {
        $this->actingAs($this->superadmin);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertSuccessful()
            ->assertActionExists('delete');
    }

    /** @test */
    public function cannot_delete_organization_with_users(): void
    {
        $this->actingAs($this->superadmin);

        // Create a user for the organization
        User::factory()->create([
            'tenant_id' => $this->organization->id,
            'role' => UserRole::ADMIN,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertNotified();

        // Organization should still exist
        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function cannot_delete_organization_with_properties(): void
    {
        $this->actingAs($this->superadmin);

        // Create a property for the organization
        Property::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertNotified();

        // Organization should still exist
        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function cannot_delete_organization_with_buildings(): void
    {
        $this->actingAs($this->superadmin);

        // Create a building for the organization
        Building::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertNotified();

        // Organization should still exist
        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function cannot_delete_organization_with_invoices(): void
    {
        $this->actingAs($this->superadmin);

        // Create an invoice for the organization
        Invoice::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertNotified();

        // Organization should still exist
        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function cannot_delete_organization_with_meters(): void
    {
        $this->actingAs($this->superadmin);

        // Create a meter for the organization
        Meter::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertNotified();

        // Organization should still exist
        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function cannot_delete_organization_with_tenants(): void
    {
        $this->actingAs($this->superadmin);

        // Create a tenant for the organization
        Tenant::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertNotified();

        // Organization should still exist
        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function can_delete_organization_without_relations(): void
    {
        $this->actingAs($this->superadmin);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertRedirect();

        // Organization should be deleted
        $this->assertDatabaseMissing('organizations', [
            'id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function deleting_organization_removes_activity_logs(): void
    {
        $this->actingAs($this->superadmin);

        // Create activity logs
        OrganizationActivityLog::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
        ]);

        $this->assertDatabaseCount('organization_activity_logs', 3);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertRedirect();

        // Activity logs should be deleted
        $this->assertDatabaseMissing('organization_activity_logs', [
            'organization_id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function deleting_organization_removes_invitations(): void
    {
        $this->actingAs($this->superadmin);

        // Create invitations
        OrganizationInvitation::factory()->count(2)->create([
            'organization_id' => $this->organization->id,
        ]);

        $this->assertDatabaseCount('organization_invitations', 2);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertRedirect();

        // Invitations should be deleted
        $this->assertDatabaseMissing('organization_invitations', [
            'organization_id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function deleting_organization_removes_super_admin_audit_logs(): void
    {
        $this->actingAs($this->superadmin);

        // Create super admin audit logs
        SuperAdminAuditLog::factory()->count(2)->create([
            'tenant_id' => $this->organization->id,
            'admin_id' => $this->superadmin->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertRedirect();

        // Super admin audit logs should be deleted
        $this->assertDatabaseMissing('super_admin_audit_logs', [
            'tenant_id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function deleting_organization_with_all_deletable_relations_succeeds(): void
    {
        $this->actingAs($this->superadmin);

        // Create only deletable relations
        OrganizationActivityLog::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
        ]);
        
        OrganizationInvitation::factory()->count(2)->create([
            'organization_id' => $this->organization->id,
        ]);
        
        SuperAdminAuditLog::factory()->count(2)->create([
            'tenant_id' => $this->organization->id,
            'admin_id' => $this->superadmin->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertRedirect();

        // Organization and all relations should be deleted
        $this->assertDatabaseMissing('organizations', [
            'id' => $this->organization->id,
        ]);
        
        $this->assertDatabaseMissing('organization_activity_logs', [
            'organization_id' => $this->organization->id,
        ]);
        
        $this->assertDatabaseMissing('organization_invitations', [
            'organization_id' => $this->organization->id,
        ]);
        
        $this->assertDatabaseMissing('super_admin_audit_logs', [
            'tenant_id' => $this->organization->id,
        ]);
    }

    /** @test */
    public function non_superadmin_cannot_delete_organization(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $this->organization->id,
        ]);

        $this->actingAs($admin);

        $this->get(route('filament.superadmin.resources.organizations.index'))
            ->assertForbidden();
    }

    /** @test */
    public function delete_action_shows_confirmation_modal(): void
    {
        $this->actingAs($this->superadmin);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->assertActionExists('delete')
            ->assertActionHasConfirmation('delete');
    }

    /** @test */
    public function cannot_delete_organization_with_multiple_relation_types(): void
    {
        $this->actingAs($this->superadmin);

        // Create multiple types of relations
        User::factory()->create([
            'tenant_id' => $this->organization->id,
            'role' => UserRole::ADMIN,
        ]);
        
        Property::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);
        
        Building::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);

        Livewire::test(
            \App\Filament\Resources\OrganizationResource\Pages\ViewOrganization::class,
            ['record' => $this->organization->id]
        )
            ->callAction('delete')
            ->assertNotified();

        // Organization should still exist
        $this->assertDatabaseHas('organizations', [
            'id' => $this->organization->id,
        ]);
    }
}
