<?php

declare(strict_types=1);

use App\Enums\HelpArticleCategory;
use App\Enums\HelpAudienceRole;
use App\Enums\UserRole;
use App\Filament\Support\Help\HelpRepository;
use App\Filament\Support\Help\SetupChecklistBuilder;
use App\Models\Building;
use App\Models\HelpArticle;
use App\Models\HelpContext;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\ServiceConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('allows admins to access admin help articles', function (): void {
    $admin = signInAs(UserRole::ADMIN);

    $this->get(route('filament.admin.pages.help'))
        ->assertSuccessful()
        ->assertSeeText('Help Center')
        ->assertSeeText('Service Configuration')
        ->assertSeeText('Billing Flow');

    expect(app(HelpRepository::class)->articlesFor($admin)->pluck('slug')->all())
        ->toContain('service-configuration')
        ->toContain('billing-flow');
});

it('allows managers to access manager-allowed help articles', function (): void {
    $manager = signInAs(UserRole::MANAGER);

    expect(app(HelpRepository::class)->articlesFor($manager)->pluck('slug')->all())
        ->toContain('service-configuration')
        ->toContain('billing-flow');
});

it('allows tenants to access tenant help but not admin help', function (): void {
    $tenant = TenantPortalFactory::new()->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-help'))
        ->assertSuccessful()
        ->assertSeeText('How to Submit Readings')
        ->assertDontSeeText('Service Configuration');

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.help'))
        ->assertForbidden();
});

it('hides inactive help articles', function (): void {
    $admin = signInAs(UserRole::ADMIN);

    HelpArticle::factory()
        ->forRole(HelpAudienceRole::ADMIN)
        ->inactive()
        ->create([
            'slug' => 'hidden-admin-guide',
            'title' => 'Hidden admin guide',
        ]);

    expect(app(HelpRepository::class)->articlesFor($admin)->pluck('title')->all())
        ->not->toContain('Hidden admin guide');
});

it('shows contextual help for configured admin and tenant pages', function (): void {
    $admin = signInAs(UserRole::ADMIN);
    $tenant = TenantPortalFactory::new()->create();
    $repository = app(HelpRepository::class);

    expect($repository->contextFor($admin, 'service_configurations.index')->pluck('slug')->all())
        ->toContain('service-configuration')
        ->and($repository->contextFor($admin, 'invoices.review')->pluck('slug')->all())
        ->toContain('billing-flow')
        ->and($repository->contextFor($admin, 'tenant.invitation')->pluck('slug')->all())
        ->toContain('tenant-onboarding')
        ->and($repository->contextFor($admin, 'rental_contracts.create')->pluck('slug')->all())
        ->toContain('rental-contracts')
        ->and($repository->contextFor($tenant->user, 'tenant.readings')->pluck('slug')->all())
        ->toContain('tenant-submit-readings');
});

it('shows database contextual help when a page context is added', function (): void {
    $admin = signInAs(UserRole::ADMIN);

    $article = HelpArticle::factory()
        ->forRole(HelpAudienceRole::ADMIN)
        ->create([
            'slug' => 'custom-invoice-review-guide',
            'title' => 'Custom invoice review guide',
            'category' => HelpArticleCategory::INVOICES,
            'body' => 'Review custom invoice blockers.',
        ]);

    HelpContext::factory()
        ->forPage('invoices.review')
        ->forArticle($article)
        ->forRole(HelpAudienceRole::ADMIN)
        ->create();

    expect(app(HelpRepository::class)->contextFor($admin, 'invoices.review')->pluck('title')->all())
        ->toContain('Custom invoice review guide');
});

it('uses selected locale and falls back to english when a localized article is missing', function (): void {
    $workspace = createOrgWithAdmin();
    $admin = User::factory()
        ->admin()
        ->withLocale('lt')
        ->create(['organization_id' => $workspace['organization']->id]);

    HelpArticle::factory()
        ->forRole(HelpAudienceRole::ADMIN)
        ->forLocale('en')
        ->create([
            'slug' => 'localized-billing-guide',
            'title' => 'English billing guide',
            'category' => HelpArticleCategory::BILLING,
        ]);

    HelpArticle::factory()
        ->forRole(HelpAudienceRole::ADMIN)
        ->forLocale('lt')
        ->create([
            'slug' => 'localized-billing-guide',
            'title' => 'Lietuviskas billing guide',
            'category' => HelpArticleCategory::BILLING,
        ]);

    HelpArticle::factory()
        ->forRole(HelpAudienceRole::ADMIN)
        ->forLocale('en')
        ->create([
            'slug' => 'english-only-guide',
            'title' => 'English fallback guide',
            'category' => HelpArticleCategory::BILLING,
        ]);

    $repository = app(HelpRepository::class);

    expect($repository->articleFor($admin, 'localized-billing-guide')?->title)
        ->toBe('Lietuviskas billing guide')
        ->and($repository->articleFor($admin, 'english-only-guide')?->title)
        ->toBe('English fallback guide');
});

it('searches title and body while respecting role visibility and active state', function (): void {
    $admin = signInAs(UserRole::ADMIN);
    $tenant = TenantPortalFactory::new()->create();

    HelpArticle::factory()
        ->forRole(HelpAudienceRole::ADMIN)
        ->create([
            'slug' => 'admin-needle-guide',
            'title' => 'Needle admin guide',
            'body' => 'Visible billing needle content.',
        ]);

    HelpArticle::factory()
        ->forRole(HelpAudienceRole::TENANT)
        ->create([
            'slug' => 'tenant-needle-guide',
            'title' => 'Needle tenant guide',
            'body' => 'Visible tenant needle content.',
        ]);

    HelpArticle::factory()
        ->forRole(HelpAudienceRole::ADMIN)
        ->inactive()
        ->create([
            'slug' => 'inactive-needle-guide',
            'title' => 'Needle inactive guide',
            'body' => 'Hidden needle content.',
        ]);

    $repository = app(HelpRepository::class);

    expect($repository->articlesFor($admin, search: 'needle')->pluck('slug')->all())
        ->toContain('admin-needle-guide')
        ->not->toContain('tenant-needle-guide')
        ->not->toContain('inactive-needle-guide')
        ->and($repository->articlesFor($tenant->user, search: 'needle')->pluck('slug')->all())
        ->toContain('tenant-needle-guide')
        ->not->toContain('admin-needle-guide');
});

it('updates the setup checklist after creating property tenant service invitation and invoice records', function (): void {
    $workspace = createOrgWithAdmin();
    $organization = $workspace['organization'];
    $admin = $workspace['admin'];
    $builder = app(SetupChecklistBuilder::class);

    expect(checklistItem($builder->forUser($admin), 'property')['complete'])->toBeFalse()
        ->and(checklistItem($builder->forUser($admin), 'tenant')['complete'])->toBeFalse()
        ->and(checklistItem($builder->forUser($admin), 'services')['complete'])->toBeFalse()
        ->and(checklistItem($builder->forUser($admin), 'invoice')['complete'])->toBeFalse();

    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create(['organization_id' => $organization->id]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    Meter::factory()
        ->for($organization)
        ->for($property)
        ->create();

    ServiceConfiguration::factory()
        ->for($organization)
        ->for($property)
        ->create();

    OrganizationInvitation::factory()
        ->for($organization)
        ->for($tenant, 'tenant')
        ->create([
            'sent_at' => now(),
        ]);

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $checklist = $builder->forUser($admin);

    expect(checklistItem($checklist, 'building')['complete'])->toBeTrue()
        ->and(checklistItem($checklist, 'property')['complete'])->toBeTrue()
        ->and(checklistItem($checklist, 'tenant')['complete'])->toBeTrue()
        ->and(checklistItem($checklist, 'assignment')['complete'])->toBeTrue()
        ->and(checklistItem($checklist, 'meters')['complete'])->toBeTrue()
        ->and(checklistItem($checklist, 'services')['complete'])->toBeTrue()
        ->and(checklistItem($checklist, 'invitation')['complete'])->toBeTrue()
        ->and(checklistItem($checklist, 'invoice')['complete'])->toBeTrue();
});

/**
 * @param  array<int, array<string, mixed>>  $checklist
 * @return array<string, mixed>
 */
function checklistItem(array $checklist, string $key): array
{
    $item = collect($checklist)->firstWhere('key', $key);

    expect($item)->toBeArray();

    return $item;
}
