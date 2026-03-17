<?php

namespace Database\Factories;

use App\Models\Country;
use App\Support\Geography\BalticReferenceCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    public function definition(): array
    {
        $country = fake()->randomElement(BalticReferenceCatalog::countries());

        return [
            'code' => $country['code'],
            'slug' => $country['slug'],
            'name' => $country['name'],
            'native_name' => $country['native_name'],
            'name_translations' => $country['name_translations'],
            'region' => $country['region'],
            'currency_code' => $country['currency_code'],
            'timezone' => $country['timezone'],
            'is_active' => $country['is_active'],
        ];
    }
}
