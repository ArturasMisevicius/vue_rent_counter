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
        ->set('profileForm.locale', 'lt')
        ->call('saveProfile')
        ->assertHasErrors(['email']);

    expect($admin->fresh())
        ->name->toBe($admin->name)
        ->email->toBe($admin->email)
        ->locale->toBe('en');
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

it('shows admin settings sections and saves organization settings plus notification preferences', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    OrganizationSetting::factory()->for($organization)->create([
        'billing_contact_name' => 'Initial Billing Team',
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
        ->set('notificationForm.new_invoice_generated', true)
        ->set('notificationForm.invoice_overdue', true)
        ->set('notificationForm.tenant_submits_reading', true)
        ->set('notificationForm.subscription_expiring', true)
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
            'new_invoice_generated' => true,
            'invoice_overdue' => true,
            'tenant_submits_reading' => true,
            'subscription_expiring' => true,
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
