<?php

namespace App\Filament\Actions\Admin\ServiceConfigurations;

use App\Enums\AuditLogAction;
use App\Enums\ServiceConfigurationStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Facades\DB;

final class DuplicateServiceConfigurationAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(ServiceConfiguration $serviceConfiguration): ServiceConfiguration
    {
        return DB::transaction(function () use ($serviceConfiguration): ServiceConfiguration {
            $serviceConfiguration->loadMissing('utilityService:id,name');

            $duplicate = $serviceConfiguration->replicate([
                'status',
                'is_active',
                'validation_result',
                'created_at',
                'updated_at',
            ]);

            $duplicate->forceFill([
                'service_name' => __('admin.service_configurations.messages.duplicate_name', [
                    'name' => $serviceConfiguration->service_name ?: $serviceConfiguration->utilityService?->name ?: $serviceConfiguration->id,
                ]),
                'status' => ServiceConfigurationStatus::DRAFT,
                'is_active' => false,
                'validation_result' => null,
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $duplicate,
                [
                    'context' => [
                        'mutation' => 'service_configuration.duplicated',
                        'source_service_configuration_id' => $serviceConfiguration->id,
                    ],
                    'after' => $duplicate->getAttributes(),
                ],
                auth()->id(),
                'Service configuration duplicated',
            );

            return $duplicate;
        });
    }
}
