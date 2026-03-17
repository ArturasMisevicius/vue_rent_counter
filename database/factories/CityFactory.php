<?php

namespace Database\Factories;

use App\Filament\Support\Geography\BalticReferenceCatalog;
use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    public function definition(): array
    {
        $city = fake()->randomElement(BalticReferenceCatalog::cities());
        $country = BalticReferenceCatalog::countriesByCode()[$city['country_code']];

        return [
            'country_id' => Country::factory()->state([
                'code' => $country['code'],
                'slug' => $country['slug'],
                'name' => $country['name'],
                'native_name' => $country['native_name'],
                'name_translations' => $country['name_translations'],
                'region' => $country['region'],
                'currency_code' => $country['currency_code'],
                'timezone' => $country['timezone'],
                'is_active' => $country['is_active'],
            ]),
            'slug' => $city['slug'],
            'name' => $city['name'],
            'native_name' => $city['native_name'],
            'name_translations' => $city['name_translations'],
            'timezone' => $city['timezone'],
            'postal_code_pattern' => $city['postal_code_pattern'],
            'latitude' => $city['latitude'],
            'longitude' => $city['longitude'],
            'is_capital' => $city['is_capital'],
            'population' => $city['population'],
        ];
    }
}
