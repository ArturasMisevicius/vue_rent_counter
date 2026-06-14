<?php

namespace App\Filament\Actions\Admin\ServiceConfigurations;

use App\Enums\AuditLogAction;
use App\Enums\ServiceConfigurationStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Facades\DB;

final class ArchiveServiceConfigurationAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(ServiceConfiguration $serviceConfiguration): ServiceConfiguration
    {
        return DB::transaction(function () use ($serviceConfiguration): ServiceConfiguration {
            $before = $serviceConfiguration->getAttributes();

            $serviceConfiguration->forceFill([
                'status' => ServiceConfigurationStatus::ARCHIVED,
                'is_active' => false,
            ])->save();

            $freshServiceConfiguration = $serviceConfiguration->fresh();

            $this->auditLogger->record(
                AuditLogAction::ARCHIVED,
                $freshServiceConfiguration,
                [
                    'context' => [
                        'mutation' => 'service_configuration.archived',
                    ],
                    'before' => $before,
                    'after' => $freshServiceConfiguration->getAttributes(),
                ],
                auth()->id(),
                'Service configuration archived',
            );

            return $freshServiceConfiguration;
        });
    }
}
