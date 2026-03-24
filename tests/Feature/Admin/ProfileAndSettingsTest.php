<?php

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Pages\Profile;
use App\Filament\Pages\Settings;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
        'phone' => '+37060000000',
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful()
        ->assertSeeText('My Profile')
        ->assertSeeText('Personal Information')
        ->assertSeeText('Change Password')
        ->assertSeeText('Phone Number')
        ->assertSeeText('Preferred Language')
        ->assertSeeText('Save Changes')
        ->assertSee('value="'.$admin->name.'"', false)
        ->assertSee('value="'.$admin->email.'"', false)
        ->assertSee('value="'.$admin->phone.'"', false);
});

it('updates the admin profile details', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'locale' => 'en',
    ]);

    Livewire::actingAs($admin)
        ->test(Profile::class)
        ->set('profileForm.name', 'Taylor Updated')
        ->set('profileForm.email', 'taylor.updated@example.com')
        ->set('profileForm.phone', '+37061234567')
        ->call('saveChanges')
        ->assertHasNoErrors();

    expect($admin->fresh())
        ->name->toBe('Taylor Updated')
        ->email->toBe('taylor.updated@example.com')
        ->phone->toBe('+37061234567')
        ->locale->toBe('en');
});

it('auto-saves the admin locale when the preferred language changes', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'locale' => 'en',
    ]);

    Livewire::actingAs($admin)
        ->test(Profile::class)
        ->set('profileForm.locale', 'lt')
        ->assertHasNoErrors();

    expect($admin->fresh()->locale)->toBe('lt');
});

it('rejects disposable email domains when updating the admin profile', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'locale' => 'en',
    ]);

    Livewire::actingAs($admin)
        ->test(Profile::class)
        ->set('profileForm.name', 'Taylor Updated')
        ->set('profileForm.email', 'profile-owner@10minutemail.com')
        ->set('profileForm.phone', '+37061234567')
        ->call('saveChanges')
        ->assertHasErrors(['profileForm.email']);

    expect($admin->fresh())
        ->name->toBe($admin->name)
        ->email->toBe($admin->email)
        ->phone->toBeNull()
        ->locale->toBe('en');
});

it('shows an immediate password confirmation error on the profile page', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Profile::class)
        ->set('passwordForm.current_password', 'password')
        ->set('passwordForm.password', 'new-password-123')
        ->set('passwordForm.password_confirmation', 'different-password')
        ->assertHasErrors(['passwordForm.password_confirmation']);
});

it('updates the admin password from the profile page when requested', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($admin)
        ->test(Profile::class)
        ->set('passwordForm.current_password', 'password')
        ->set('passwordForm.password', 'new-password-123')
        ->set('passwordForm.password_confirmation', 'new-password-123')
        ->call('saveChanges')
        ->assertHasNoErrors();

    expect(Hash::check('new-password-123', $admin->fresh()->password))->toBeTrue();
});

it('refreshes translated admin profile copy when the shell locale changes', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'locale' => 'en',
    ]);

    $component = Livewire::actingAs($admin)
        ->test(Profile::class)
        ->assertSeeText(__('shell.profile.title', [], 'en'))
        ->assertSeeText(__('shell.profile.personal_information.heading', [], 'en'));

    $admin->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($admin->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('shell.profile.title', [], 'lt'))
        ->assertSeeText(__('shell.profile.personal_information.heading', [], 'lt'));
});

it('shows the admin settings page with organization, notification, and subscription sections only', function () {
    $organization = Organization::factory()->create([
        'name' => 'North Block Properties',
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    OrganizationSetting::factory()->for($organization)->create();
    Subscription::factory()->for($organization)->active()->create([
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 25,
    ]);
    Property::factory()->count(3)->for($organization)->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.settings'))
        ->assertSuccessful()
        ->assertSeeText('Settings')
        ->assertSeeText('Organization Settings')
        ->assertSeeText('Notification Preferences')
        ->assertSeeText('Subscription')
        ->assertSeeText('Organization Name')
        ->assertSeeText('Billing Email Address')
        ->assertSeeText('Default Invoice Footer Notes')
        ->assertSeeText('Renew or Upgrade Plan')
        ->assertDontSeeText('Personal Information')
        ->assertDontSeeText('Change Password');
});

it('restricts the settings page to admins', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.settings'))
        ->assertForbidden();
});

it('saves organization settings and notification preferences from the admin settings page', function () {
    $organization = Organization::factory()->create([
        'name' => 'Initial Org',
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    OrganizationSetting::factory()->for($organization)->create([
        'billing_contact_email' => 'before@example.com',
        'invoice_footer' => 'Before footer',
        'notification_preferences' => [
            'new_invoice_generated' => false,
            'invoice_overdue' => false,
            'tenant_submits_reading' => false,
            'subscription_expiring' => false,
        ],
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
    ]);

    Livewire::actingAs($admin)
        ->test(Settings::class)
        ->set('organizationForm.organization_name', 'Updated Org')
        ->set('organizationForm.billing_contact_email', 'billing@example.com')
        ->set('organizationForm.invoice_footer', 'Thank you for paying on time.')
        ->call('saveSettings')
        ->assertHasNoErrors()
        ->set('notificationForm.new_invoice_generated', true)
        ->assertHasNoErrors();

    $settings = $organization->fresh()->settings;

    expect($organization->fresh()->name)->toBe('Updated Org')
        ->and($settings)->not->toBeNull()
        ->and($settings?->billing_contact_email)->toBe('billing@example.com')
        ->and($settings?->invoice_footer)->toBe('Thank you for paying on time.')
        ->and($settings?->notification_preferences['new_invoice_generated'] ?? false)->toBeTrue();
});

it('allows admins to renew or upgrade the current organization subscription from settings', function () {
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
        ->call('openSubscriptionPanel')
        ->assertSet('showSubscriptionPanel', true)
        ->set('renewalForm.plan', SubscriptionPlan::PROFESSIONAL->value)
        ->set('renewalForm.duration', SubscriptionDuration::QUARTERLY->value)
        ->call('renewSubscription')
        ->assertHasNoErrors()
        ->assertSet('showSubscriptionPanel', false);

    $subscription->refresh();

    expect($subscription->plan)->toBe(SubscriptionPlan::PROFESSIONAL)
        ->and($subscription->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($subscription->is_trial)->toBeFalse()
        ->and($subscription->expires_at->greaterThan(now()->addMonths(2)))->toBeTrue()
        ->and($subscription->property_limit_snapshot)->toBe(SubscriptionPlan::PROFESSIONAL->limits()['properties']);
});

it('refreshes translated admin settings copy when the shell locale changes', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'locale' => 'en',
    ]);

    OrganizationSetting::factory()->for($organization)->create();

    $component = Livewire::actingAs($admin)
        ->test(Settings::class)
        ->assertSeeText(__('shell.settings.organization.heading', [], 'en'))
        ->assertSeeText(__('shell.settings.notifications.heading', [], 'en'));

    $admin->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($admin->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('shell.settings.organization.heading', [], 'lt'))
        ->assertSeeText(__('shell.settings.notifications.heading', [], 'lt'));
});
