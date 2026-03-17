<?php

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Pages\Profile;
use App\Filament\Pages\Settings;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects admin-like users from the shared profile route to the Filament profile page', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->get(route('profile.edit'))
        ->assertRedirect(route('filament.admin.pages.profile'));
});

it('shows the admin profile page with personal information and password sections', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful()
        ->assertSeeText('My Profile')
        ->assertSeeText('Personal Information')
        ->assertSeeText('Change Password')
        ->assertSee('value="'.$admin->name.'"', false)
        ->assertSee('value="'.$admin->email.'"', false);
});

it('updates the admin profile details and locale', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'locale' => 'en',
    ]);

    Livewire::actingAs($admin)
        ->test(Profile::class)
        ->set('profileForm.name', 'Taylor Updated')
        ->set('profileForm.email', 'taylor.updated@example.com')
        ->set('profileForm.locale', 'lt')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($admin->fresh())
        ->name->toBe('Taylor Updated')
        ->email->toBe('taylor.updated@example.com')
        ->locale->toBe('lt');
});

it('updates the admin password from the profile page', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Profile::class)
        ->set('passwordForm.current_password', 'password')
        ->set('passwordForm.password', 'new-password-123')
        ->set('passwordForm.password_confirmation', 'new-password-123')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password-123', $admin->fresh()->password))->toBeTrue();
});

it('shows admin settings sections and saves organization settings plus notification preferences', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    OrganizationSetting::factory()->for($organization)->create([
        'billing_contact_name' => 'Initial Billing Team',
        'notification_preferences' => [
            'invoice_reminders' => false,
            'reading_deadline_alerts' => false,
        ],
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.settings'))
        ->assertSuccessful()
        ->assertSeeText('Settings')
        ->assertSeeText('Personal Information')
        ->assertSeeText('Change Password')
        ->assertSeeText('Organization Settings')
        ->assertSeeText('Notification Preferences')
        ->assertSeeText('Subscription');

    Livewire::actingAs($admin)
        ->test(Settings::class)
        ->set('organizationForm.billing_contact_name', 'Updated Billing Team')
        ->set('organizationForm.billing_contact_email', 'billing@example.com')
        ->set('organizationForm.billing_contact_phone', '+37060000000')
        ->set('organizationForm.payment_instructions', 'Pay by bank transfer.')
        ->set('organizationForm.invoice_footer', 'Thank you for paying on time.')
        ->call('saveOrganizationSettings')
        ->assertHasNoErrors()
        ->set('notificationForm.invoice_reminders', true)
        ->set('notificationForm.reading_deadline_alerts', true)
        ->call('saveNotificationPreferences')
        ->assertHasNoErrors();

    $settings = $organization->fresh()->settings;

    expect($settings)->not->toBeNull()
        ->and($settings?->billing_contact_name)->toBe('Updated Billing Team')
        ->and($settings?->billing_contact_email)->toBe('billing@example.com')
        ->and($settings?->billing_contact_phone)->toBe('+37060000000')
        ->and($settings?->payment_instructions)->toBe('Pay by bank transfer.')
        ->and($settings?->invoice_footer)->toBe('Thank you for paying on time.')
        ->and($settings?->notification_preferences)->toBe([
            'invoice_reminders' => true,
            'reading_deadline_alerts' => true,
        ]);
});

it('allows admins to renew the current organization subscription from settings', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $subscription = Subscription::factory()->for($organization)->create([
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::TRIALING,
        'is_trial' => true,
        'starts_at' => now()->subDays(10),
        'expires_at' => now()->addDay(),
    ]);

    Livewire::actingAs($admin)
        ->test(Settings::class)
        ->set('renewalForm.plan', SubscriptionPlan::PROFESSIONAL->value)
        ->set('renewalForm.duration', SubscriptionDuration::QUARTERLY->value)
        ->call('renewSubscription')
        ->assertHasNoErrors();

    $subscription->refresh();

    expect($subscription->plan)->toBe(SubscriptionPlan::PROFESSIONAL)
        ->and($subscription->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($subscription->is_trial)->toBeFalse()
        ->and($subscription->expires_at->greaterThan(now()->addMonths(2)))->toBeTrue()
        ->and($subscription->property_limit_snapshot)->toBe(SubscriptionPlan::PROFESSIONAL->limits()['properties']);
});
