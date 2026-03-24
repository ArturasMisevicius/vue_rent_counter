<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders tenant pages inside the authenticated shell', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText(__('shell.search.placeholder'))
        ->assertSeeText(__('tenant.navigation.home'))
        ->assertSeeText(__('shell.navigation.items.profile'))
        ->assertSee('data-shell-nav="sidebar"', false)
        ->assertDontSeeText(__('admin.buildings.plural'))
        ->assertDontSeeText(__('superadmin.organizations.plural'));
});

it('does not register echo listeners when broadcasting is configured to log', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertDontSee('echo-private:org.', false);
});

it('renders role-aware shared chrome around organization admin pages', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSeeText(__('shell.search.placeholder'))
        ->assertSee('data-shell-nav="sidebar"', false)
        ->assertSee('data-shell-group="properties"', false)
        ->assertSee('data-shell-group="billing"', false)
        ->assertSee('data-shell-group="reports"', false)
        ->assertSee('data-shell-group="account"', false)
        ->assertSeeText(__('admin.buildings.plural'))
        ->assertSeeText(__('admin.properties.plural'))
        ->assertSeeText(__('shell.navigation.items.reports'))
        ->assertSeeText(__('shell.navigation.groups.account'))
        ->assertSeeText(__('shell.navigation.items.settings'))
        ->assertSeeText(__('dashboard.logout_button'))
        ->assertSee(route('filament.admin.resources.buildings.index'), false)
        ->assertSee(route('filament.admin.resources.properties.index'), false)
        ->assertDontSeeText(__('superadmin.organizations.plural'))
        ->assertDontSeeText(__('shell.navigation.items.users'))
        ->assertDontSeeText(__('shell.navigation.items.subscriptions'))
        ->assertDontSeeText(__('shell.navigation.items.translations'))
        ->assertDontSeeText(__('shell.navigation.items.audit_logs'))
        ->assertDontSeeText(__('shell.navigation.items.platform_notifications'))
        ->assertDontSeeText(__('shell.navigation.items.languages'))
        ->assertDontSeeText(__('shell.navigation.items.security_violations'))
        ->assertDontSeeText(__('shell.navigation.items.integration_health'))
        ->assertDontSee('data-shell-group="platform"', false);
});

it('renders platform navigation for superadmins without organization navigation', function () {
    $superadmin = User::factory()->superadmin()->create();

    $response = $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertSuccessful()
        ->assertSeeText(__('shell.search.placeholder'))
        ->assertSee('data-shell-nav="sidebar"', false)
        ->assertSee('data-shell-group="platform"', false)
        ->assertSee('data-shell-group="properties"', false)
        ->assertSee('data-shell-group="billing"', false)
        ->assertSee('data-shell-group="reports"', false)
        ->assertSee('data-shell-group="account"', false)
        ->assertSee(route('filament.admin.resources.organizations.index'), false)
        ->assertSee(route('filament.admin.resources.service-configurations.index'), false)
        ->assertSee(route('filament.admin.resources.utility-services.index'), false)
        ->assertSee(route('filament.admin.pages.platform-notifications'), false)
        ->assertSeeText(__('shell.navigation.groups.platform'))
        ->assertSeeText(__('shell.navigation.groups.properties'))
        ->assertSeeText(__('shell.navigation.groups.billing'))
        ->assertSeeText(__('shell.navigation.groups.reports'))
        ->assertSeeText(__('shell.navigation.groups.account'))
        ->assertSeeText(__('dashboard.logout_button'));

    $content = $response->getContent();

    expect($content)->toContain('wire:navigate');

    assertSidebarGroupLabels($content, 'platform', [
        'Organizations',
        'Users',
        'Organization Users',
        'Subscriptions',
        'Projects',
        'Tasks',
        'Task Assignments',
        'Time Entries',
        'Comments',
        'Comment Reactions',
        'Attachments',
        'Tags',
        'Property Assignments',
        'Invoice Items',
        'Invoice Payments',
        'Invoice Reminder Logs',
        'Invoice Email Logs',
        'Subscription Payments',
        'Subscription Renewals',
        'System Configuration',
        'Audit Logs',
        'Platform Notifications',
        'Languages',
        'Translations',
        'Security Violations',
        'Integration Health',
    ]);

    assertSidebarGroupLabels($content, 'properties', [
        'Buildings',
        'Properties',
        'Tenants',
        'Meters',
        'Meter Readings',
    ]);

    assertSidebarGroupLabels($content, 'billing', [
        'Invoices',
        'Tariffs',
        'Providers',
        'Service Configurations',
        'Utility Services',
    ]);

    assertSidebarGroupLabels($content, 'reports', [
        'Reports',
    ]);

    assertSidebarGroupLabels($content, 'account', [
        'Profile',
        'Settings',
    ]);
});

it('renders localized superadmin sidebar labels from the authenticated user locale', function () {
    $superadmin = User::factory()->superadmin()->create([
        'locale' => 'lt',
    ]);

    $response = $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertSuccessful();

    $content = $response->getContent();

    assertSidebarGroupLabels($content, 'platform', [
        __('superadmin.organizations.plural', [], 'lt'),
        __('shell.navigation.items.users', [], 'lt'),
        __('shell.navigation.items.organization_users', [], 'lt'),
        __('shell.navigation.items.subscriptions', [], 'lt'),
        __('shell.navigation.items.projects', [], 'lt'),
        __('shell.navigation.items.tasks', [], 'lt'),
        __('shell.navigation.items.task_assignments', [], 'lt'),
        __('shell.navigation.items.time_entries', [], 'lt'),
        __('shell.navigation.items.comments', [], 'lt'),
        __('shell.navigation.items.comment_reactions', [], 'lt'),
        __('shell.navigation.items.attachments', [], 'lt'),
        __('shell.navigation.items.tags', [], 'lt'),
        __('shell.navigation.items.property_assignments', [], 'lt'),
        __('shell.navigation.items.invoice_items', [], 'lt'),
        __('shell.navigation.items.invoice_payments', [], 'lt'),
        __('shell.navigation.items.invoice_reminder_logs', [], 'lt'),
        __('shell.navigation.items.invoice_email_logs', [], 'lt'),
        __('shell.navigation.items.subscription_payments', [], 'lt'),
        __('shell.navigation.items.subscription_renewals', [], 'lt'),
        __('shell.navigation.items.system_configuration', [], 'lt'),
        __('shell.navigation.items.audit_logs', [], 'lt'),
        __('shell.navigation.items.platform_notifications', [], 'lt'),
        __('shell.navigation.items.languages', [], 'lt'),
        __('shell.navigation.items.translations', [], 'lt'),
        __('shell.navigation.items.security_violations', [], 'lt'),
        __('shell.navigation.items.integration_health', [], 'lt'),
    ]);
});

it('renders localized admin sidebar labels from the authenticated user locale', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'locale' => 'lt',
        'organization_id' => $organization->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful();

    $content = $response->getContent();

    assertSidebarGroupLabels($content, 'properties', [
        __('admin.buildings.plural', [], 'lt'),
        __('admin.properties.plural', [], 'lt'),
        __('admin.tenants.plural', [], 'lt'),
        __('admin.meters.plural', [], 'lt'),
        __('admin.meter_readings.plural', [], 'lt'),
    ]);

    assertSidebarGroupLabels($content, 'billing', [
        __('admin.invoices.plural', [], 'lt'),
        __('admin.tariffs.plural', [], 'lt'),
        __('admin.providers.plural', [], 'lt'),
        __('admin.service_configurations.plural', [], 'lt'),
        __('admin.utility_services.plural', [], 'lt'),
    ]);

    assertSidebarGroupLabels($content, 'reports', [
        __('shell.navigation.items.reports', [], 'lt'),
    ]);

    assertSidebarGroupLabels($content, 'account', [
        __('shell.navigation.items.profile', [], 'lt'),
        __('shell.navigation.items.settings', [], 'lt'),
    ]);
});

it('renders localized tenant sidebar labels from the authenticated user locale', function () {
    $organization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'locale' => 'lt',
        'organization_id' => $organization->id,
    ]);

    $response = $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful();

    $content = $response->getContent();

    assertSidebarGroupLabels($content, 'my_home', [
        __('tenant.navigation.home', [], 'lt'),
        __('tenant.pages.property.title', [], 'lt'),
        __('tenant.navigation.readings', [], 'lt'),
        __('tenant.navigation.invoices', [], 'lt'),
    ]);

    assertSidebarGroupLabels($content, 'account', [
        __('shell.navigation.items.profile', [], 'lt'),
    ]);
});

it('redirects admin-like users from the shared profile route into the filament-backed profile page', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->get(route('profile.edit'))
        ->assertRedirect(route('filament.admin.pages.profile'));
});

/**
 * @param  list<string>  $labels
 */
function assertSidebarGroupLabels(string $content, string $groupKey, array $labels): void
{
    $document = new DOMDocument;

    libxml_use_internal_errors(true);
    $document->loadHTML($content);
    libxml_clear_errors();

    $xpath = new DOMXPath($document);
    $nodes = $xpath->query(sprintf(
        '//nav[@data-shell-nav="sidebar"]//section[@data-shell-group="%s"]//a',
        $groupKey,
    ));

    expect($nodes)->not->toBeFalse();

    $actualLabels = [];

    foreach ($nodes ?: [] as $node) {
        $actualLabels[] = trim(preg_replace('/\s+/', ' ', $node->textContent ?? '') ?? '');
    }

    expect($actualLabels)->toBe($labels);
}
