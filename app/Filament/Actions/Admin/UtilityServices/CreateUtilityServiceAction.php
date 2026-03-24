<?php

namespace App\Filament\Actions\Admin\UtilityServices;

use App\Enums\ServiceType;
use App\Enums\UnitOfMeasurement;
use App\Http\Requests\Admin\UtilityServices\UtilityServiceRequest;
use App\Models\Organization;
use App\Models\UtilityService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateUtilityServiceAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Organization $organization, array $attributes): UtilityService
    {
        $validated = $this->validate($attributes);

        return DB::transaction(function () use ($organization, $validated): UtilityService {
            return UtilityService::query()->create([
                'organization_id' => $organization->id,
                'name' => (string) $validated['name'],
                'unit_of_measurement' => $this->resolveUnitValue($validated),
                'default_pricing_model' => $validated['default_pricing_model'],
                'calculation_formula' => Arr::get($validated, 'calculation_formula'),
                'is_global_template' => false,
                'created_by_organization_id' => $organization->id,
                'configuration_schema' => Arr::get($validated, 'configuration_schema'),
                'validation_rules' => Arr::get($validated, 'validation_rules'),
                'business_logic_config' => Arr::get($validated, 'business_logic_config'),
                'service_type_bridge' => $validated['service_type_bridge'] ?? null,
                'description' => Arr::get($validated, 'description'),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validate(array $attributes): array
    {
        /** @var UtilityServiceRequest $request */
        $request = new UtilityServiceRequest;

        return $request->validatePayload($attributes);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveUnitValue(array $validated): string
    {
        $unit = $validated['unit_of_measurement'] ?? null;

        if ($unit instanceof UnitOfMeasurement) {
            return $unit->value;
        }

        if (is_string($unit) && $unit !== '') {
            return $unit;
        }

        $serviceType = $validated['service_type_bridge'] instanceof ServiceType
            ? $validated['service_type_bridge']
            : ServiceType::from((string) $validated['service_type_bridge']);

        return $serviceType->defaultUnit()->value;
    }
}
