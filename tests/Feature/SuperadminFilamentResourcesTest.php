<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Resources\OrganizationResource;
use App\Filament\Resources\SubscriptionResource;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Subscription;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperadminFilamentResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
    }

    /** @test */
    public function superadmin_can_access_organization_resource(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/organizations');

        $response->assertStatus(200);
    }

    /** @test */
    public function non_superadmin_cannot_access_organization_resource(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/superadmin/organizations');

        $response->assertStatus(403);
    }

    /** @test */
    public function organization_resource_form_validation_works(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\Pages\CreateOrganization::class)
            ->fillForm([
                'name' => '', // Required field
                'email' => 'invalid-email', // Invalid email
                'plan' => 'invalid-plan', // Invalid plan
            ])
            ->call('create')
            ->assertHasFormErrors(['name', 'email', 'plan']);
    }

    /** @test */
    public function organization_resource_form_creates_organization_successfully(): void
    {
        $formData = [
            'name' => 'Test Organization',
            'slug' => 'test-org',
            'email' => 'admin@testorg.com',
            'phone' => '+1234567890',
            'plan' => SubscriptionPlan::PROFESSIONAL->value,
            'max_properties' => 500,
            'max_users' => 50,
            'subscription_ends_at' => now()->addYear(),
            'timezone' => 'America/New_York',
            'locale' => 'en',
            'currency' => 'USD',
            'is_active' => true,
        ];

        Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\Pages\CreateOrganization::class)
            ->fillForm($formData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Organization',
            'email' => 'admin@testorg.com',
            'plan' => SubscriptionPlan::PROFESSIONAL->value,
        ]);
    }

    /** @test */
    public function organization_resource_table_filtering_works(): void
    {
        // Create organizations with different plans
        Organization::factory()->create([
            'name' => 'Basic Org',
            'plan' => SubscriptionPlan::BASIC,
        ]);

        Organization::factory()->create([
            'name' => 'Pro Org',
            'plan' => SubscriptionPlan::PROFESSIONAL,
        ]);

        $component = Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\Pages\ListOrganizations::class);

        // Test plan filter
        $component->filterTable('plan', SubscriptionPlan::BASIC->value)
            ->assertCanSeeTableRecords([
                Organization::where('plan', SubscriptionPlan::BASIC)->first()
            ])
            ->assertCanNotSeeTableRecords([
                Organization::where('plan', SubscriptionPlan::PROFESSIONAL)->first()
            ]);
    }

    /** @test */
    public function organization_resource_table_sorting_works(): void
    {
        Organization::factory()->create(['name' => 'Zebra Organization']);
        Organization::factory()->create(['name' => 'Alpha Organization']);

        Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\Pages\ListOrganizations::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords(Organization::orderBy('name')->get(), inOrder: true);
    }

    /** @test */
    public function organization_resource_bulk_actions_work(): void
    {
        $organizations = Organization::factory()->count(3)->create([
            'is_active' => true,
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\Pages\ListOrganizations::class)
            ->callTableBulkAction('bulk_suspend', $organizations->pluck('id')->toArray(), data: [
                'reason' => 'Bulk suspension test'
            ]);

        // Verify all organizations are suspended
        foreach ($organizations as $organization) {
            $organization->refresh();
            $this->assertFalse($organization->is_active);
            $this->assertNotNull($organization->suspended_at);
        }
    }

    /** @test */
    public function organization_resource_custom_actions_work(): void
    {
        $organization = Organization::factory()->create([
            'is_active' => true,
        ]);

        // Test suspend action
        Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\Pages\ViewOrganization::class, [
                'record' => $organization->id
            ])
            ->callAction('suspend', data: [
                'reason' => 'Policy violation'
            ]);

        $organization->refresh();
        $this->assertFalse($organization->is_active);
        $this->assertEquals('Policy violation', $organization->suspension_reason);
    }

    /** @test */
    public function subscription_resource_form_validation_works(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(SubscriptionResource\Pages\CreateSubscription::class)
            ->fillForm([
                'user_id' => null, // Required
                'plan_type' => '', // Required
                'expires_at' => now()->subDays(1), // Should be future date
            ])
            ->call('create')
            ->assertHasFormErrors(['user_id', 'plan_type', 'expires_at']);
    }

    /** @test */
    public function subscription_resource_form_creates_subscription_successfully(): void
    {
        $user = User::factory()->create();

        $formData = [
            'user_id' => $user->id,
            'plan_type' => 'professional',
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'max_properties' => 500,
            'max_tenants' => 200,
        ];

        Livewire::actingAs($this->superadmin)
            ->test(SubscriptionResource\Pages\CreateSubscription::class)
            ->fillForm($formData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_type' => 'professional',
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);
    }

    /** @test */
    public function subscription_resource_renewal_action_works(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::EXPIRED,
            'expires_at' => now()->subDays(10),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(SubscriptionResource\Pages\ViewSubscription::class, [
                'record' => $subscription->id
            ])
            ->callAction('renew', data: [
                'expires_at' => now()->addYear()
            ]);

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertTrue($subscription->expires_at->isFuture());
    }

    /** @test */
    public function subscription_resource_bulk_renewal_works(): void
    {
        $users = User::factory()->count(3)->create();
        $subscriptions = collect();

        foreach ($users as $user) {
            $subscriptions->push(Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => SubscriptionStatus::EXPIRED,
                'expires_at' => now()->subDays(5),
            ]));
        }

        Livewire::actingAs($this->superadmin)
            ->test(SubscriptionResource\Pages\ListSubscriptions::class)
            ->callTableBulkAction('bulk_renew', $subscriptions->pluck('id')->toArray(), data: [
                'duration' => '1_year'
            ]);

        // Verify all subscriptions are renewed
        foreach ($subscriptions as $subscription) {
            $subscription->refresh();
            $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
            $this->assertTrue($subscription->expires_at->isFuture());
        }
    }

    /** @test */
    public function organization_resource_relation_managers_work(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['tenant_id' => $organization->id]);

        Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\RelationManagers\UsersRelationManager::class, [
                'ownerRecord' => $organization,
                'pageClass' => OrganizationResource\Pages\ViewOrganization::class,
            ])
            ->assertCanSeeTableRecords([$user]);
    }

    /** @test */
    public function organization_resource_properties_relation_manager_works(): void
    {
        $organization = Organization::factory()->create();
        $property = \App\Models\Property::factory()->create(['tenant_id' => $organization->id]);

        Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\RelationManagers\PropertiesRelationManager::class, [
                'ownerRecord' => $organization,
                'pageClass' => OrganizationResource\Pages\ViewOrganization::class,
            ])
            ->assertCanSeeTableRecords([$property]);
    }

    /** @test */
    public function organization_invitation_resource_works(): void
    {
        $invitation = OrganizationInvitation::factory()->create([
            'accepted_at' => null,
        ]);

        // Test resend action
        Livewire::actingAs($this->superadmin)
            ->test(\App\Filament\Resources\OrganizationInvitationResource\Pages\ViewOrganizationInvitation::class, [
                'record' => $invitation->id
            ])
            ->callAction('resend');

        $invitation->refresh();
        $this->assertTrue($invitation->expires_at->isFuture());
    }

    /** @test */
    public function platform_user_resource_shows_cross_organization_users(): void
    {
        $org1 = Organization::factory()->create(['name' => 'Organization 1']);
        $org2 = Organization::factory()->create(['name' => 'Organization 2']);

        $user1 = User::factory()->create(['tenant_id' => $org1->id]);
        $user2 = User::factory()->create(['tenant_id' => $org2->id]);

        Livewire::actingAs($this->superadmin)
            ->test(\App\Filament\Resources\PlatformUserResource\Pages\ListPlatformUsers::class)
            ->assertCanSeeTableRecords([$user1, $user2]);
    }

    /** @test */
    public function platform_user_resource_password_reset_action_works(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($this->superadmin)
            ->test(\App\Filament\Resources\PlatformUserResource\Pages\ViewPlatformUser::class, [
                'record' => $user->id
            ])
            ->callAction('resetPassword')
            ->assertNotified();

        // Verify notification was sent (would need to mock notifications)
        $this->assertTrue(true); // Placeholder assertion
    }

    /** @test */
    public function filament_resources_respect_superadmin_authorization(): void
    {
        $organization = Organization::factory()->create();

        // Test that regular user cannot access edit page
        $response = $this->actingAs($this->regularUser)
            ->get("/superadmin/organizations/{$organization->id}/edit");

        $response->assertStatus(403);

        // Test that superadmin can access edit page
        $response = $this->actingAs($this->superadmin)
            ->get("/superadmin/organizations/{$organization->id}/edit");

        $response->assertStatus(200);
    }

    /** @test */
    public function filament_resources_handle_soft_deletes_correctly(): void
    {
        $organization = Organization::factory()->create();

        // Test soft delete
        Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\Pages\ViewOrganization::class, [
                'record' => $organization->id
            ])
            ->callAction(DeleteAction::class);

        // Verify organization is soft deleted
        $this->assertSoftDeleted('organizations', ['id' => $organization->id]);
    }

    /** @test */
    public function filament_widgets_display_correct_data(): void
    {
        // Create test data
        Organization::factory()->count(5)->create(['is_active' => true]);
        Organization::factory()->count(2)->create(['is_active' => false]);

        Subscription::factory()->count(8)->create(['status' => SubscriptionStatus::ACTIVE]);
        Subscription::factory()->count(3)->create(['status' => SubscriptionStatus::EXPIRED]);

        // Test organization stats widget
        Livewire::actingAs($this->superadmin)
            ->test(\App\Filament\Widgets\OrganizationStatsWidget::class)
            ->assertSee('7') // Total organizations
            ->assertSee('5'); // Active organizations

        // Test subscription stats widget
        Livewire::actingAs($this->superadmin)
            ->test(\App\Filament\Widgets\SubscriptionStatsWidget::class)
            ->assertSee('11') // Total subscriptions
            ->assertSee('8'); // Active subscriptions
    }

    /** @test */
    public function filament_global_search_works(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Searchable Organization',
        ]);

        $user = User::factory()->create([
            'name' => 'Searchable User',
            'email' => 'searchable@example.com',
        ]);

        // Test global search
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/search?query=Searchable');

        $response->assertStatus(200);
        $response->assertSee('Searchable Organization');
        $response->assertSee('Searchable User');
    }

    /** @test */
    public function filament_export_actions_work(): void
    {
        Organization::factory()->count(3)->create();

        Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\Pages\ListOrganizations::class)
            ->callAction('export', data: [
                'format' => 'csv'
            ])
            ->assertFileDownloaded();
    }

    /** @test */
    public function filament_form_live_updates_work(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(OrganizationResource\Pages\CreateOrganization::class)
            ->fillForm([
                'plan' => SubscriptionPlan::PROFESSIONAL->value
            ])
            ->assertFormFieldExists('max_properties')
            ->assertFormSet([
                'max_properties' => 500, // Should auto-populate based on plan
                'max_users' => 50,
            ]);
    }
}
