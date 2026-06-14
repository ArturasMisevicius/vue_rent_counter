<?php

namespace App\Filament\Actions\Admin\ServiceConfigurations;

use App\Enums\AuditLogAction;
use App\Filament\Actions\Admin\ServiceConfigurations\Concerns\InteractsWithServiceConfigurationAttributes;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Facades\DB;

class UpdateServiceConfigurationAction
{
    use InteractsWithServiceConfigurationAttributes;

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(ServiceConfiguration $serviceConfiguration, array $attributes): ServiceConfiguration
    {
        $organization = $serviceConfiguration->organization()->select(['id'])->firstOrFail();

        $this->guardReferences($organization, $attributes);

        return DB::transaction(function () use ($serviceConfiguration, $attributes, $organization): ServiceConfiguration {
            $before = $serviceConfiguration->getAttributes();

            $serviceConfiguration->fill($this->serviceConfigurationPayload($attributes, $organization->id));
            $serviceConfiguration->save();

            $freshServiceConfiguration = $serviceConfiguration->fresh();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $freshServiceConfiguration,
                [
                    'context' => [
                        'mutation' => 'service_configuration.updated',
                    ],
                    'before' => $before,
                    'after' => $freshServiceConfiguration->getAttributes(),
                ],
                auth()->id(),
                'Service configuration updated',
            );

            return $freshServiceConfiguration;
        });
    }
}
