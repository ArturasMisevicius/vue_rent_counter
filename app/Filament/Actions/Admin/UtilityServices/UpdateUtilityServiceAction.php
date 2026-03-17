<?php

namespace App\Filament\Actions\Admin\UtilityServices;

use App\Models\UtilityService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateUtilityServiceAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(UtilityService $utilityService, array $attributes): UtilityService
    {
        return DB::transaction(function () use ($utilityService, $attributes): UtilityService {
            $utilityService->fill([
                'name' => (string) $attributes['name'],
                'slug' => $this->resolveSlug($utilityService, (string) $attributes['name']),
                'unit_of_measurement' => (string) $attributes['unit_of_measurement'],
                'default_pricing_model' => $attributes['default_pricing_model'],
                'calculation_formula' => Arr::get($attributes, 'calculation_formula'),
                'configuration_schema' => Arr::get($attributes, 'configuration_schema'),
                'validation_rules' => Arr::get($attributes, 'validation_rules'),
                'business_logic_config' => Arr::get($attributes, 'business_logic_config'),
                'service_type_bridge' => $attributes['service_type_bridge'] ?? null,
                'description' => Arr::get($attributes, 'description'),
                'is_active' => (bool) ($attributes['is_active'] ?? true),
            ]);
            $utilityService->save();

            return $utilityService->fresh();
        });
    }

    private function resolveSlug(UtilityService $utilityService, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (UtilityService::query()
            ->where('slug', $slug)
            ->whereKeyNot($utilityService->id)
            ->exists()) {
            $slug = "{$base}-{$utilityService->organization_id}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
