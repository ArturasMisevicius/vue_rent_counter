<?php

declare(strict_types=1);

namespace App\Services\Meter;

use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Collection;

/**
 * Service for handling meter service configuration logic.
 * 
 * Provides methods for retrieving and processing service configurations
 * for meter creation and management.
 */
final readonly class MeterServiceConfigurationService
{
    /**
     * Get active service configurations for a property.
     * 
     * @param Property $property The property to get configurations for
     * @return array<int, string> Array of configuration ID => label pairs
     */
    public function getActiveConfigurationsForProperty(Property $property): array
    {
        return $this->getConfigurationsQuery($property->id)
            ->get()
            ->mapWithKeys(fn (ServiceConfiguration $config): array => [
                $config->id => $this->formatConfigurationLabel($config),
            ])
            ->all();
    }

    /**
     * Get meter type and zone support for a service configuration.
     * 
     * @param int $configurationId The service configuration ID
     * @return array{type: string, supports_zones: bool}
     */
    public function getMeterConfigurationDefaults(int $configurationId): array
    {
        $config = ServiceConfiguration::with('utilityService')->find($configurationId);
        
        if (!$config) {
            return [
                'type' => MeterType::CUSTOM->value,
                'supports_zones' => false,
            ];
        }

        return [
            'type' => MeterType::CUSTOM->value,
            'supports_zones' => $config->pricing_model === PricingModel::TIME_OF_USE,
        ];
    }

    /**
     * Get the base query for service configurations.
     */
    private function getConfigurationsQuery(int $propertyId): \Illuminate\Database\Eloquent\Builder
    {
        return ServiceConfiguration::query()
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->with('utilityService:id,name,unit_of_measurement')
            ->orderBy('effective_from', 'desc');
    }

    /**
     * Format a service configuration label for display.
     */
    private function formatConfigurationLabel(ServiceConfiguration $config): string
    {
        $service = $config->utilityService;

        if (!$service) {
            return "Service #{$config->id}";
        }

        return "{$service->name} ({$service->unit_of_measurement})";
    }
}