<?php

declare(strict_types=1);

use App\Actions\CreateOrganizationAction;
use App\Enums\SubscriptionPlanType;
use App\Enums\UserRole;
use App\Filament\Resources\OrganizationResource\Pages\CreateOrganization;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\OrganizationOwnerInvitationNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('superadmin');
});

it('auto-generates a slug from the organization name and preserves manual overrides', function (): void {
    $superadmin = User::factory()->superadmin()->create();

    actingAs($superadmin);

    livewire(CreateOrganization::class)
        ->fillForm([
            'name' => 'North Star Estates',
        ])
        ->assertSchemaStateSet([
            'slug' => 'north-star-estates',
        ])
        ->fillForm([
            'slug' => 'custom-north-star',
            'name' => 'Updated Organization Name',
        ])
        ->assertSchemaStateSet([
            'slug' => 'custom-north-star',
        ]);
});

it('creates an organization, reassigns an existing owner, and provisions a subscription', function (): void {
    Notification::fake();

    $superadmin = User::factory()->superadmin()->create();
    $existingOwner = User::factory()->tenant()->create([
        'email' => 'owner@example.com',
        'name' => 'Existing Owner',
    ]);

    actingAs($superadmin);

    $organization = app(CreateOrganizationAction::class)->handle(
        organizationName: 'Lakeside Portfolio',
        slug: 'lakeside-portfolio',
        ownerEmail: 'owner@example.com',
        plan: SubscriptionPlanType::PROFESSIONAL,
        durationInMonths: 3,
        actor: $superadmin,
    );

    expect($organization)
        ->toBeInstanceOf(Organization::class)
        ->and($organization->name)->toBe('Lakeside Portfolio')
        ->and($organization->slug)->toBe('lakeside-portfolio');

    $existingOwner->refresh();

    expect($existingOwner->tenant_id)->toBe($organization->id)
        ->and($existingOwner->role)->toBe(UserRole::ADMIN)
        ->and($existingOwner->organization_name)->toBe('Lakeside Portfolio');

    $subscription = Subscription::query()
        ->where('user_id', $existingOwner->id)
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription?->plan_type)->toBe(SubscriptionPlanType::PROFESSIONAL->value);

    Notification::assertNotSentTo(
        $existingOwner,
        OrganizationOwnerInvitationNotification::class,
    );
});

it('creates a new owner account and sends an invitation notification when the email is new', function (): void {
    Notification::fake();

    $superadmin = User::factory()->superadmin()->create();

    actingAs($superadmin);

    $organization = app(CreateOrganizationAction::class)->handle(
        organizationName: 'Aurora Holdings',
        slug: 'aurora-holdings',
        ownerEmail: 'new.owner@example.com',
        plan: SubscriptionPlanType::ENTERPRISE,
        durationInMonths: 12,
        actor: $superadmin,
    );

    $owner = User::query()
        ->select(['id', 'tenant_id', 'email', 'role', 'organization_name'])
        ->where('email', 'new.owner@example.com')
        ->first();

    expect($owner)->not->toBeNull()
        ->and($owner?->tenant_id)->toBe($organization->id)
        ->and($owner?->role)->toBe(UserRole::ADMIN)
        ->and($owner?->organization_name)->toBe('Aurora Holdings');

    Notification::assertSentTo(
        $owner,
        OrganizationOwnerInvitationNotification::class,
    );

    expect(
        Subscription::query()
            ->where('user_id', $owner?->id)
            ->where('plan_type', SubscriptionPlanType::ENTERPRISE->value)
            ->exists()
    )->toBeTrue();
});
