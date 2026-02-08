<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization;

use App\Enums\MeterType;
use App\Models\UtilityService;

/**
 * Provides default meter configurations for utility services.
 * 
 * This class creates meter configuration templates based on utility service
 * types and their specific requirements, including reading structures,
 * validation rules, and photo verification settings.
 * 
 * @package App\Services\TenantInitialization
 * @author Laravel Development Team
 * @since 1.0.0
 */
final readonly class MeterConfigurationProvider
{
    /**
     * Create default meter configurations for utility services.
     * 
     * @param array<string, UtilityService> $utilityServices Array of utility services keyed by service type
     * 
     * @return array<string, array<string, mixed>> Meter configurations keyed by service type
     */
    public function createDefaultMeterConfigurations(array $utilityServices): array
    {
        $configurations = [];

        foreach ($utilityServices as $serviceType => $utilityService) {
            $configurations[$serviceType] = $this->createMeterConfigurationForService(
                $serviceType,
                $utilityService
            );
        }

        return $configurations;
    }

    /**
     * Create meter configuration for a specific utility service.
     * 
     * @return array<string, mixed>
     */
    private function createMeterConfigurationForService(string $serviceType, UtilityService $utilityService): array
    {
        $baseConfig = [
            'utility_service_id' => $utilityService->id,
            'service_type' => $serviceType,
            'unit_of_measurement' => $utilityService->unit_of_measurement,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return match ($serviceType) {
            'electricity' => array_merge($baseConfig, $this->getElectricityMeterConfig()),
            'water' => array_merge($baseConfig, $this->getWaterMeterConfig()),
            'heating' => array_merge($baseConfig, $this->getHeatingMeterConfig()),
            'gas' => array_merge($baseConfig, $this->getGasMeterConfig()),
            default => array_merge($baseConfig, $this->getDefaultMeterConfig()),
        };
    }

    /**
     * Get electricity meter configuration.
     * 
     * @return array<string, mixed>
     */
    private function getElectricityMeterConfig(): array
    {
        return [
            'meter_type' => MeterType::ELECTRICITY,
            'supports_zones' => true,
            'reading_structure' => [
                'zones' => [
                    'day' => [
                        'name' => 'Day Rate',
                        'description' => 'Daytime electricity consumption',
                        'active_hours' => '07:00-23:00',
                        'required' => true,
                    ],
                    'night' => [
                        'name' => 'Night Rate',
                        'description' => 'Nighttime electricity consumption',
                        'active_hours' => '23:00-07:00',
                        'required' => true,
                    ],
                ],
                'total_field' => 'total_consumption',
                'validation' => [
                    'day_plus_night_equals_total' => true,
                    'monotonic_increase' => true,
                ],
            ],
            'validation_rules' => [
                'max_consumption' => 10000,
                'variance_threshold' => 0.5,
                'require_monotonic' => true,
                'allow_estimated' => true,
                'estimation_max_days' => 7,
            ],
            'requires_photo_verification' => true,
            'photo_requirements' => [
                'min_resolution' => '640x480',
                'max_file_size' => '5MB',
                'accepted_formats' => ['jpg', 'jpeg', 'png'],
                'require_meter_serial' => true,
            ],
            'reading_frequency' => 'monthly',
            'auto_estimation_enabled' => true,
        ];
    }

    /**
     * Get water meter configuration.
     * 
     * @return array<string, mixed>
     */
    private function getWaterMeterConfig(): array
    {
        return [
            'meter_type' => MeterType::WATER,
            'supports_zones' => false,
            'reading_structure' => [
                'fields' => [
                    'cold_water' => [
                        'name' => 'Cold Water',
                        'description' => 'Cold water consumption',
                        'unit' => 'm³',
                        'required' => true,
                    ],
                    'hot_water' => [
                        'name' => 'Hot Water',
                        'description' => 'Hot water consumption',
                        'unit' => 'm³',
                        'required' => false,
                    ],
                ],
                'total_field' => 'total_consumption',
                'validation' => [
                    'monotonic_increase' => true,
                    'reasonable_consumption' => true,
                ],
            ],
            'validation_rules' => [
                'max_consumption' => 1000,
                'variance_threshold' => 0.3,
                'require_monotonic' => true,
                'allow_estimated' => true,
                'estimation_max_days' => 14,
            ],
            'requires_photo_verification' => false,
            'reading_frequency' => 'monthly',
            'auto_estimation_enabled' => true,
            'supports_hot_cold_split' => true,
        ];
    }

    /**
     * Get heating meter configuration.
     * 
     * @return array<string, mixed>
     */
    private function getHeatingMeterConfig(): array
    {
        return [
            'meter_type' => MeterType::HEATING,
            'supports_zones' => false,
            'reading_structure' => [
                'fields' => [
                    'energy_consumption' => [
                        'name' => 'Energy Consumption',
                        'description' => 'Heating energy consumption',
                        'unit' => 'kWh',
                        'required' => true,
                    ],
                    'flow_temperature' => [
                        'name' => 'Flow Temperature',
                        'description' => 'Supply water temperature',
                        'unit' => '°C',
                        'required' => false,
                    ],
                    'return_temperature' => [
                        'name' => 'Return Temperature',
                        'description' => 'Return water temperature',
                        'unit' => '°C',
                        'required' => false,
                    ],
                ],
                'total_field' => 'energy_consumption',
                'validation' => [
                    'seasonal_variance' => true,
                    'temperature_correlation' => true,
                ],
            ],
            'validation_rules' => [
                'max_consumption' => 5000,
                'variance_threshold' => 0.4,
                'require_monotonic' => false, // Heating can have seasonal resets
                'allow_estimated' => true,
                'estimation_max_days' => 30,
                'seasonal_adjustment' => true,
            ],
            'requires_photo_verification' => false,
            'reading_frequency' => 'monthly',
            'auto_estimation_enabled' => true,
            'seasonal_settings' => [
                'summer_months' => [5, 6, 7, 8, 9],
                'winter_months' => [11, 12, 1, 2, 3],
                'transition_months' => [4, 10],
            ],
        ];
    }

    /**
     * Get gas meter configuration.
     * 
     * @return array<string, mixed>
     */
    private function getGasMeterConfig(): array
    {
        return [
            'meter_type' => MeterType::GAS,
            'supports_zones' => false,
            'reading_structure' => [
                'fields' => [
                    'volume_consumption' => [
                        'name' => 'Volume Consumption',
                        'description' => 'Gas volume consumption',
                        'unit' => 'm³',
                        'required' => true,
                    ],
                    'pressure' => [
                        'name' => 'Pressure',
                        'description' => 'Gas pressure reading',
                        'unit' => 'mbar',
                        'required' => false,
                    ],
                ],
                'total_field' => 'volume_consumption',
                'validation' => [
                    'monotonic_increase' => true,
                    'pressure_correlation' => true,
                ],
            ],
            'validation_rules' => [
                'max_consumption' => 2000,
                'variance_threshold' => 0.4,
                'require_monotonic' => true,
                'allow_estimated' => true,
                'estimation_max_days' => 7,
            ],
            'requires_photo_verification' => true,
            'photo_requirements' => [
                'min_resolution' => '640x480',
                'max_file_size' => '5MB',
                'accepted_formats' => ['jpg', 'jpeg', 'png'],
                'require_meter_serial' => true,
                'safety_warning' => true,
            ],
            'reading_frequency' => 'monthly',
            'auto_estimation_enabled' => true,
            'safety_requirements' => [
                'leak_detection' => true,
                'pressure_monitoring' => true,
                'emergency_contacts' => true,
            ],
        ];
    }

    /**
     * Get default meter configuration for unknown service types.
     * 
     * @return array<string, mixed>
     */
    private function getDefaultMeterConfig(): array
    {
        return [
            'meter_type' => MeterType::OTHER,
            'supports_zones' => false,
            'reading_structure' => [
                'fields' => [
                    'consumption' => [
                        'name' => 'Consumption',
                        'description' => 'Service consumption',
                        'unit' => 'units',
                        'required' => true,
                    ],
                ],
                'total_field' => 'consumption',
                'validation' => [
                    'monotonic_increase' => true,
                ],
            ],
            'validation_rules' => [
                'max_consumption' => 1000,
                'variance_threshold' => 0.3,
                'require_monotonic' => false,
                'allow_estimated' => true,
                'estimation_max_days' => 14,
            ],
            'requires_photo_verification' => false,
            'reading_frequency' => 'monthly',
            'auto_estimation_enabled' => false,
        ];
    }

    /**
     * Get meter configuration template for a specific service type.
     * 
     * @return array<string, mixed>|null
     */
    public function getMeterConfigurationTemplate(string $serviceType): ?array
    {
        return match ($serviceType) {
            'electricity' => $this->getElectricityMeterConfig(),
            'water' => $this->getWaterMeterConfig(),
            'heating' => $this->getHeatingMeterConfig(),
            'gas' => $this->getGasMeterConfig(),
            default => $this->getDefaultMeterConfig(),
        };
    }

    /**
     * Validate meter configuration against service requirements.
     * 
     * @param array<string, mixed> $configuration
     * @return array<string> Array of validation errors, empty if valid
     */
    public function validateMeterConfiguration(array $configuration, string $serviceType): array
    {
        $errors = [];
        $template = $this->getMeterConfigurationTemplate($serviceType);

        if (!$template) {
            $errors[] = "Unknown service type: {$serviceType}";
            return $errors;
        }

        // Validate required fields
        $requiredFields = ['meter_type', 'reading_structure', 'validation_rules'];
        foreach ($requiredFields as $field) {
            if (!isset($configuration[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Validate reading structure
        if (isset($configuration['reading_structure'])) {
            $readingStructure = $configuration['reading_structure'];
            
            if (!isset($readingStructure['fields']) && !isset($readingStructure['zones'])) {
                $errors[] = 'Reading structure must define either fields or zones';
            }

            if (!isset($readingStructure['total_field'])) {
                $errors[] = 'Reading structure must define total_field';
            }
        }

        // Validate validation rules
        if (isset($configuration['validation_rules'])) {
            $validationRules = $configuration['validation_rules'];
            
            if (!isset($validationRules['max_consumption']) || $validationRules['max_consumption'] <= 0) {
                $errors[] = 'Validation rules must define positive max_consumption';
            }

            if (!isset($validationRules['variance_threshold']) || 
                $validationRules['variance_threshold'] < 0 || 
                $validationRules['variance_threshold'] > 1) {
                $errors[] = 'Validation rules must define variance_threshold between 0 and 1';
            }
        }

        return $errors;
    }
}