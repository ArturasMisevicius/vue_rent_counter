<?php

namespace App\Actions\Admin\UtilityServices;

use App\Models\Organization;
use App\Models\UtilityService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateUtilityServiceAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Organization $organization, array $attributes): UtilityService
    {
        return DB::transaction(function () use ($organization, $attributes): UtilityService {
            return UtilityService::query()->create([
                'organization_id' => $organization->id,
                'name' => (string) $attributes['name'],
                'slug' => $this->uniqueSlug((string) $attributes['name'], $organization->id),
                'unit_of_measurement' => (string) $attributes['unit_of_measurement'],
                'default_pricing_model' => $attributes['default_pricing_model'],
                'calculation_formula' => Arr::get($attributes, 'calculation_formula'),
                'is_global_template' => false,
                'created_by_organization_id' => $organization->id,
                'configuration_schema' => Arr::get($attributes, 'configuration_schema'),
                'validation_rules' => Arr::get($attributes, 'validation_rules'),
                'business_logic_config' => Arr::get($attributes, 'business_logic_config'),
                'service_type_bridge' => $attributes['service_type_bridge'] ?? null,
                'description' => Arr::get($attributes, 'description'),
                'is_active' => (bool) ($attributes['is_active'] ?? true),
            ]);
        });
    }

    private function uniqueSlug(string $name, int $organizationId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (UtilityService::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$organizationId}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
