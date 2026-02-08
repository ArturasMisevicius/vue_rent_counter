<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Assign Utility Service DTO
 * 
 * Data transfer object for assigning utility services to properties.
 * Provides type safety and validation for service assignment data.
 * 
 * @package App\DTOs
 */
final readonly class AssignUtilityServiceDTO
{
    public function __construct(
        public int $propertyId,
        public int $utilityServiceId,
        public PricingModel $pricingModel,
        public ?array $rateSchedule = null,
        public ?DistributionMethod $distributionMethod = null,
        public bool $isSharedService = false,
        public ?Carbon $effectiveFrom = null,
        public ?Carbon $effectiveUntil = null,
        public ?array $configurationOverrides = null,
        public ?int $tariffId = null,
        public ?int $providerId = null,
        public ?string $areaType = null,
        public ?string $customFormula = null,
        public bool $isActive = true,
    ) {}

    /**
     * Create DTO from HTTP request.
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            propertyId: (int) $request->input('property_id'),
            utilityServiceId: (int) $request->input('utility_service_id'),
            pricingModel: PricingModel::from($request->input('pricing_model')),
            rateSchedule: $request->input('rate_schedule'),
            distributionMethod: $request->has('distribution_method') 
                ? DistributionMethod::from($request->input('distribution_method'))
                : null,
            isSharedService: $request->boolean('is_shared_service', false),
            effectiveFrom: $request->has('effective_from') 
                ? Carbon::parse($request->input('effective_from'))
                : now(),
            effectiveUntil: $request->has('effective_until')
                ? Carbon::parse($request->input('effective_until'))
                : null,
            configurationOverrides: $request->input('configuration_overrides'),
            tariffId: $request->has('tariff_id') ? (int) $request->input('tariff_id') : null,
            providerId: $request->has('provider_id') ? (int) $request->input('provider_id') : null,
            areaType: $request->input('area_type'),
            customFormula: $request->input('custom_formula'),
            isActive: $request->boolean('is_active', true),
        );
    }

    /**
     * Create DTO from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            propertyId: (int) $data['property_id'],
            utilityServiceId: (int) $data['utility_service_id'],
            pricingModel: $data['pricing_model'] instanceof PricingModel 
                ? $data['pricing_model'] 
                : PricingModel::from($data['pricing_model']),
            rateSchedule: $data['rate_schedule'] ?? null,
            distributionMethod: isset($data['distribution_method'])
                ? ($data['distribution_method'] instanceof DistributionMethod
                    ? $data['distribution_method']
                    : DistributionMethod::from($data['distribution_method']))
                : null,
            isSharedService: $data['is_shared_service'] ?? false,
            effectiveFrom: isset($data['effective_from'])
                ? ($data['effective_from'] instanceof Carbon 
                    ? $data['effective_from']
                    : Carbon::parse($data['effective_from']))
                : now(),
            effectiveUntil: isset($data['effective_until'])
                ? ($data['effective_until'] instanceof Carbon
                    ? $data['effective_until']
                    : Carbon::parse($data['effective_until']))
                : null,
            configurationOverrides: $data['configuration_overrides'] ?? null,
            tariffId: isset($data['tariff_id']) ? (int) $data['tariff_id'] : null,
            providerId: isset($data['provider_id']) ? (int) $data['provider_id'] : null,
            areaType: $data['area_type'] ?? null,
            customFormula: $data['custom_formula'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    /**
     * Convert to array for model creation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'property_id' => $this->propertyId,
            'utility_service_id' => $this->utilityServiceId,
            'pricing_model' => $this->pricingModel,
            'rate_schedule' => $this->rateSchedule,
            'distribution_method' => $this->distributionMethod,
            'is_shared_service' => $this->isSharedService,
            'effective_from' => $this->effectiveFrom,
            'effective_until' => $this->effectiveUntil,
            'configuration_overrides' => $this->configurationOverrides,
            'tariff_id' => $this->tariffId,
            'provider_id' => $this->providerId,
            'area_type' => $this->areaType,
            'custom_formula' => $this->customFormula,
            'is_active' => $this->isActive,
        ];
    }
}
