<?php

namespace App\Actions\Admin\Buildings;

use App\Models\Building;
use App\Models\Organization;

class CreateBuildingAction
{
    /**
     * @param  array{
     *     name: string,
     *     address_line_1: string,
     *     address_line_2: string|null,
     *     city: string,
     *     postal_code: string,
     *     country_code: string
     * }  $attributes
     */
    public function handle(Organization $organization, array $attributes): Building
    {
        $building = new Building([
            'name' => $attributes['name'],
            'address_line_1' => $attributes['address_line_1'],
            'address_line_2' => $attributes['address_line_2'],
            'city' => $attributes['city'],
            'postal_code' => $attributes['postal_code'],
            'country_code' => strtoupper($attributes['country_code']),
        ]);

        $building->organization()->associate($organization);
        $building->save();

        return $building->refresh();
    }
}
