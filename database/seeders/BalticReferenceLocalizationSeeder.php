<?php

namespace Database\Seeders;

use App\Filament\Support\Geography\BalticReferenceCatalog;
use App\Models\City;
use App\Models\Country;
use App\Models\Translation;
use Illuminate\Database\Seeder;

class BalticReferenceLocalizationSeeder extends Seeder
{
    public function run(): void
    {
        $locales = array_keys(config('tenanto.locales', ['en' => true, 'lt' => true, 'ru' => true, 'es' => true]));

        $countries = collect(BalticReferenceCatalog::countries())
            ->mapWithKeys(function (array $country) use ($locales): array {
                $translations = array_replace(array_fill_keys($locales, null), $country['name_translations']);

                $record = Country::query()->updateOrCreate(
                    ['code' => $country['code']],
                    [
                        'slug' => $country['slug'],
                        'name' => $country['name'],
                        'native_name' => $country['native_name'],
                        'name_translations' => $country['name_translations'],
                        'region' => $country['region'],
                        'currency_code' => $country['currency_code'],
                        'timezone' => $country['timezone'],
                        'is_active' => $country['is_active'],
                    ],
                );

                Translation::query()->updateOrCreate(
                    [
                        'group' => 'countries',
                        'key' => $country['code'],
                    ],
                    [
                        'values' => $translations,
                    ],
                );

                return [$country['code'] => $record];
            });

        foreach (BalticReferenceCatalog::cities() as $city) {
            $translations = array_replace(array_fill_keys($locales, null), $city['name_translations']);
            $country = $countries[$city['country_code']];

            City::query()->updateOrCreate(
                [
                    'country_id' => $country->id,
                    'slug' => $city['slug'],
                ],
                [
                    'name' => $city['name'],
                    'native_name' => $city['native_name'],
                    'name_translations' => $city['name_translations'],
                    'timezone' => $city['timezone'],
                    'postal_code_pattern' => $city['postal_code_pattern'],
                    'latitude' => $city['latitude'],
                    'longitude' => $city['longitude'],
                    'is_capital' => $city['is_capital'],
                    'population' => $city['population'],
                ],
            );

            Translation::query()->updateOrCreate(
                [
                    'group' => 'cities',
                    'key' => $city['slug'],
                ],
                [
                    'values' => $translations,
                ],
            );
        }
    }
}
