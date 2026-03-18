<?php

use App\Enums\OrganizationStatus;
use App\Enums\PlatformNotificationSeverity;
use App\Filament\Pages\TranslationManagement;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Models\Language;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('blocks admins from every platform control plane url', function () {
    [$organization, $managedUser, $subscription, $notification, $language] = seedPlatformResourceFixtures();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin);

    foreach (platformUrls($organization, $managedUser, $subscription, $notification, $language) as $url) {
        $this->get($url)->assertForbidden();
    }
});

it('allows superadmins to access every platform control plane url', function () {
    [$organization, $managedUser, $subscription, $notification, $language] = seedPlatformResourceFixtures();

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    foreach (platformUrls($organization, $managedUser, $subscription, $notification, $language) as $url) {
        $this->get($url)->assertSuccessful();
    }
});

it('suspends an organization from the view page action', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'status' => OrganizationStatus::ACTIVE,
    ]);

    $organizationAdmin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->forceFill([
        'owner_user_id' => $organizationAdmin->id,
    ])->save();

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->callAction('suspendOrganization');

    expect($organization->fresh()->status)->toBe(OrganizationStatus::SUSPENDED);
});

it('impersonates the primary admin from the organization view page', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();

    $organizationAdmin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'email' => 'owner@example.test',
    ]);

    $organization->forceFill([
        'owner_user_id' => $organizationAdmin->id,
    ])->save();

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->callAction('impersonateAdmin')
        ->assertRedirect(route('filament.admin.pages.dashboard'));

    $this->assertAuthenticatedAs($organizationAdmin);

    expect(session('impersonator_id'))->toBe($superadmin->id);
});

it('saves inline translation edits into the translations table', function () {
    $superadmin = User::factory()->superadmin()->create();

    Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'is_default' => true,
    ]);

    Language::factory()->create([
        'code' => 'lt',
        'name' => 'Lithuanian',
        'native_name' => 'Lietuviu',
    ]);

    Translation::query()->create([
        'group' => 'messages',
        'key' => 'welcome',
        'values' => [
            'en' => 'Welcome',
            'lt' => '',
        ],
    ]);

    $this->actingAs($superadmin);

    Livewire::test(TranslationManagement::class)
        ->set('draftValues.messages.welcome.en', 'Welcome back')
        ->call('saveValue', 'messages', 'welcome', 'en');

    expect(Translation::query()->where('group', 'messages')->where('key', 'welcome')->firstOrFail()->values)
        ->toMatchArray([
            'en' => 'Welcome back',
            'lt' => '',
        ]);
});

function seedPlatformResourceFixtures(): array
{
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    $managedUser = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->forceFill([
        'owner_user_id' => $managedUser->id,
    ])->save();

    $subscription = Subscription::factory()->for($organization)->active()->create();

    $notification = PlatformNotification::factory()->create([
        'severity' => PlatformNotificationSeverity::INFO,
    ]);

    $language = Language::factory()->create([
        'code' => 'de',
        'name' => 'German',
        'native_name' => 'Deutsch',
    ]);

    SystemSetting::factory()->create([
        'key' => 'platform.name',
        'label' => 'Platform Name',
        'value' => ['value' => 'Tenanto'],
    ]);

    return [$organization, $managedUser, $subscription, $notification, $language];
}

function platformUrls(
    Organization $organization,
    User $managedUser,
    Subscription $subscription,
    PlatformNotification $notification,
    Language $language,
): array {
    return [
        route('filament.admin.resources.organizations.index'),
        route('filament.admin.resources.organizations.create'),
        route('filament.admin.resources.organizations.view', $organization),
        route('filament.admin.resources.organizations.edit', $organization),
        route('filament.admin.resources.users.index'),
        route('filament.admin.resources.users.create'),
        route('filament.admin.resources.users.view', $managedUser),
        route('filament.admin.resources.users.edit', $managedUser),
        route('filament.admin.resources.subscriptions.index'),
        route('filament.admin.resources.subscriptions.create'),
        route('filament.admin.resources.subscriptions.view', $subscription),
        route('filament.admin.resources.subscriptions.edit', $subscription),
        route('filament.admin.resources.platform-notifications.index'),
        route('filament.admin.resources.platform-notifications.create'),
        route('filament.admin.resources.platform-notifications.view', $notification),
        route('filament.admin.resources.platform-notifications.edit', $notification),
        route('filament.admin.resources.languages.index'),
        route('filament.admin.resources.languages.create'),
        route('filament.admin.resources.languages.edit', $language),
        route('filament.admin.pages.translation-management'),
        route('filament.admin.pages.system-configuration'),
    ];
}
