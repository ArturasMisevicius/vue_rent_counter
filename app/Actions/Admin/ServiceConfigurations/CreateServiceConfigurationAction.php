<?php

namespace App\Actions\Admin\ServiceConfigurations;

use App\Actions\Admin\ServiceConfigurations\Concerns\InteractsWithServiceConfigurationAttributes;
use App\Models\Organization;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Facades\DB;

class CreateServiceConfigurationAction
{
    use InteractsWithServiceConfigurationAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Organization $organization, array $attributes): ServiceConfiguration
    {
        $this->guardReferences($organization, $attributes);

        return DB::transaction(fn (): ServiceConfiguration => ServiceConfiguration::query()->create(
            $this->serviceConfigurationPayload($attributes, $organization->id),
        ));
    }
}
