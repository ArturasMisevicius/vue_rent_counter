<?php

namespace App\Filament\Actions\Admin\ServiceConfigurations;

use App\Enums\AuditLogAction;
use App\Filament\Actions\Admin\ServiceConfigurations\Concerns\InteractsWithServiceConfigurationAttributes;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Organization;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Facades\DB;

class CreateServiceConfigurationAction
{
    use InteractsWithServiceConfigurationAttributes;

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Organization $organization, array $attributes): ServiceConfiguration
    {
        $this->guardReferences($organization, $attributes);

        return DB::transaction(function () use ($attributes, $organization): ServiceConfiguration {
            $serviceConfiguration = ServiceConfiguration::query()->create(
                $this->serviceConfigurationPayload($attributes, $organization->id),
            );

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $serviceConfiguration,
                [
                    'context' => [
                        'mutation' => 'service_configuration.created',
                    ],
                    'after' => $serviceConfiguration->getAttributes(),
                ],
                auth()->id(),
                'Service configuration created',
            );

            return $serviceConfiguration;
        });
    }
}
