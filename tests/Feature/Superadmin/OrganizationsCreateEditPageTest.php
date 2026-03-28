<?php

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Models\Organization;
use App\Models\PlatformOrganizationInvitation;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the create organization page without a slug field and with an expiry preview', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.create'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.organizations.pages.new'))
        ->assertSeeText(__('superadmin.organizations.actions.save'))
        ->assertSeeText('Cancel')
        ->assertSeeText(__('superadmin.organizations.form.sections.details'))
        ->assertSeeText(__('superadmin.organizations.form.sections.owner'))
        ->assertSeeText(__('superadmin.organizations.form.sections.subscription'))
        ->assertDontSeeText(__('superadmin.organizations.form.fields.url_slug'));

    $this->actingAs($superadmin);

    Livewire::test(CreateOrganization::class)
        ->assertFormFieldDoesNotExist('slug')
        ->assertFormFieldExists('plan', fn (Select $field): bool => $field->getLabel() === __('superadmin.organizations.form.fields.plan'))
        ->assertFormFieldExists('duration', fn (ToggleButtons $field): bool => $field->getLabel() === __('superadmin.organizations.form.fields.duration'))
        ->fillForm([
            'duration' => SubscriptionDuration::YEARLY->value,
        ])
        ->assertSeeText(__('superadmin.organizations.form.preview.subscription_expires', [
            'date' => CarbonImmutable::now()
                ->startOfDay()
                ->addMonths(SubscriptionDuration::YEARLY->months())
                ->locale(app()->getLocale())
                ->isoFormat('ll'),
        ]));
});

it('links existing owners on create and redirects to the organization view page', function () {
    $superadmin = User::factory()->superadmin()->create();
    $existingOwner = User::factory()->admin()->create([
        'organization_id' => null,
        'email' => 'owner@example.com',
        'name' => 'Olivia Owner',
    ]);

    $this->actingAs($superadmin);

    $component = Livewire::test(CreateOrganization::class)
        ->fillForm([
            'owner_email' => $existingOwner->email,
        ])
        ->assertSeeText(__('superadmin.organizations.form.helper.owner_existing'))
        ->fillForm([
            'name' => 'Aurora Estates',
            'owner_email' => $existingOwner->email,
            'plan' => SubscriptionPlan::PROFESSIONAL->value,
            'duration' => SubscriptionDuration::QUARTERLY->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $organization = Organization::query()->firstOrFail();
    $subscription = Subscription::query()->where('organization_id', $organization->id)->firstOrFail();

    $component->assertRedirect(route('filament.admin.resources.organizations.view', $organization));

    expect($organization->owner_user_id)->toBe($existingOwner->id)
        ->and($organization->slug)->toBe('aurora-estates')
        ->and($existingOwner->fresh()->organization_id)->toBe($organization->id)
        ->and($subscription->plan)->toBe(SubscriptionPlan::PROFESSIONAL)
        ->and(PlatformOrganizationInvitation::query()->count())->toBe(0);
});

it('creates platform invitations for new owner emails from the create page', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'name' => 'Harbor Heights',
            'owner_email' => 'invite.owner@example.com',
            'plan' => SubscriptionPlan::BASIC->value,
            'duration' => SubscriptionDuration::MONTHLY->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(PlatformOrganizationInvitation::query()
        ->where('organization_name', 'Harbor Heights')
        ->where('admin_email', 'invite.owner@example.com')
        ->exists())->toBeTrue();
});

it('renders the edit page without a slug field and updates the slug automatically when the name changes', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
        'slug' => 'northwind-towers',
    ]);

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'email' => 'owner@northwind.test',
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    $subscription = Subscription::factory()->for($organization)->create([
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
        'starts_at' => now()->subMonth(),
        'expires_at' => now()->addMonth()->startOfDay(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.edit', $organization))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.organizations.pages.edit', ['name' => $organization->name]))
        ->assertSeeText(__('superadmin.organizations.actions.save_changes'))
        ->assertSeeText('Cancel')
        ->assertDontSeeText(__('superadmin.organizations.form.fields.url_slug'));

    $this->actingAs($superadmin);

    $component = Livewire::test(EditOrganization::class, ['record' => $organization->getRouteKey()])
        ->assertFormFieldDoesNotExist('slug')
        ->assertFormFieldExists('expires_at', fn (DatePicker $field): bool => $field->getLabel() === __('superadmin.organizations.form.fields.expiry_date'))
        ->assertSchemaStateSet([
            'name' => 'Northwind Towers',
            'owner_email' => 'owner@northwind.test',
            'plan' => SubscriptionPlan::BASIC->value,
            'expires_at' => $subscription->expires_at?->toDateString(),
        ])
        ->fillForm([
            'plan' => SubscriptionPlan::PROFESSIONAL->value,
        ])
        ->assertSeeText(__('superadmin.organizations.form.preview.plan_limits', [
            'plan' => SubscriptionPlan::PROFESSIONAL->label(),
            'properties' => 50,
            'tenants' => 150,
        ]))
        ->fillForm([
            'name' => 'Northwind Towers Updated',
            'owner_email' => 'owner@northwind.test',
            'plan' => SubscriptionPlan::PROFESSIONAL->value,
            'expires_at' => now()->addMonths(6)->startOfDay()->toDateString(),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $component->assertRedirect(route('filament.admin.resources.organizations.view', $organization));

    $organization->refresh();
    $subscription->refresh();

    expect($organization->name)->toBe('Northwind Towers Updated')
        ->and($organization->slug)->toBe('northwind-towers-updated')
        ->and($subscription->plan)->toBe(SubscriptionPlan::PROFESSIONAL)
        ->and($subscription->expires_at?->toDateString())->toBe(now()->addMonths(6)->startOfDay()->toDateString());
});
