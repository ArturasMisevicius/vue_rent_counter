<?php

use App\Enums\IntegrationHealthStatus;
use App\Enums\ServiceType;
use App\Models\IntegrationHealthCheck;
use App\Models\Organization;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows platform and organization integration health on the org detail page', function () {
    $organization = Organization::factory()->create([
        'name' => 'Northwind Integrations',
        'slug' => 'northwind-integrations',
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $queueCheck = IntegrationHealthCheck::factory()->create([
        'key' => 'queue',
        'label' => __('superadmin.integration_health.probes.queue.label'),
        'status' => IntegrationHealthStatus::FAILED,
        'summary' => 'Queue worker is paused.',
        'checked_at' => now()->subMinutes(2),
        'response_time_ms' => 0,
    ]);

    $mailCheck = IntegrationHealthCheck::factory()->create([
        'key' => 'mail',
        'label' => __('superadmin.integration_health.probes.mail.label'),
        'status' => IntegrationHealthStatus::HEALTHY,
        'summary' => __('superadmin.integration_health.probes.mail.summary_healthy', ['mailer' => 'smtp']),
        'checked_at' => now()->subMinutes(5),
        'response_time_ms' => 14,
    ]);

    $configuredProvider = Provider::factory()->forOrganization($organization)->create([
        'name' => 'Nordic Grid',
        'service_type' => ServiceType::ELECTRICITY,
        'updated_at' => now()->subHour(),
    ]);

    ServiceConfiguration::factory()->create([
        'organization_id' => $organization->id,
        'provider_id' => $configuredProvider->id,
        'is_active' => true,
    ]);

    $attentionProvider = Provider::factory()->forOrganization($organization)->create([
        'name' => 'Legacy Billing Partner',
        'service_type' => ServiceType::WATER,
        'updated_at' => now()->subDays(2),
    ]);

    $integrationHealthUrl = route('filament.admin.pages.integration-health');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.view', $organization))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.organizations.overview.integration_health_heading'))
        ->assertSeeText(__('superadmin.organizations.overview.integration_health_description'))
        ->assertSeeText(__('superadmin.organizations.overview.platform_integrations_heading'))
        ->assertSeeText(__('superadmin.organizations.overview.organization_integrations_heading'))
        ->assertSeeText(__('superadmin.integration_health.probes.queue.label'))
        ->assertSeeText(__('superadmin.integration_health.probes.mail.label'))
        ->assertSeeText('Queue worker is paused.')
        ->assertSeeText($queueCheck->checked_at?->diffForHumans() ?? '')
        ->assertSeeText($mailCheck->checked_at?->diffForHumans() ?? '')
        ->assertSeeText('Nordic Grid')
        ->assertSeeText('Legacy Billing Partner')
        ->assertSeeText(__('superadmin.organizations.overview.integration_summaries.configured_provider', ['count' => 1]))
        ->assertSeeText(__('superadmin.organizations.overview.integration_summaries.needs_configuration'))
        ->assertSeeText($configuredProvider->updated_at?->diffForHumans() ?? '')
        ->assertSeeText($attentionProvider->updated_at?->diffForHumans() ?? '')
        ->assertSee($integrationHealthUrl, false);
});
