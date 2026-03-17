<?php

namespace App\Filament\Actions\Admin\ServiceConfigurations;

use App\Filament\Actions\Admin\ServiceConfigurations\Concerns\InteractsWithServiceConfigurationAttributes;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Facades\DB;

class UpdateServiceConfigurationAction
{
    use InteractsWithServiceConfigurationAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(ServiceConfiguration $serviceConfiguration, array $attributes): ServiceConfiguration
    {
        $organization = $serviceConfiguration->organization()->select(['id'])->firstOrFail();

        $this->guardReferences($organization, $attributes);

        return DB::transaction(function () use ($serviceConfiguration, $attributes, $organization): ServiceConfiguration {
            $serviceConfiguration->fill($this->serviceConfigurationPayload($attributes, $organization->id));
            $serviceConfiguration->save();

            return $serviceConfiguration->fresh();
        });
    }
}
