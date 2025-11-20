<?php

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProvidersSeeder extends Seeder
{
    /**
     * Seed the providers table with Lithuanian utility providers.
     */
    public function run(): void
    {
        // Ignitis - Electricity provider
        Provider::create([
            'name' => 'Ignitis',
            'service_type' => ServiceType::ELECTRICITY,
            'contact_info' => [
                'phone' => '+370 700 55 055',
                'email' => 'info@ignitis.lt',
                'website' => 'https://www.ignitis.lt',
            ],
        ]);

        // Vilniaus Vandenys - Water supply and sewage
        Provider::create([
            'name' => 'Vilniaus Vandenys',
            'service_type' => ServiceType::WATER,
            'contact_info' => [
                'phone' => '+370 5 266 2600',
                'email' => 'info@vv.lt',
                'website' => 'https://www.vv.lt',
            ],
        ]);

        // Vilniaus Energija - Heating provider
        Provider::create([
            'name' => 'Vilniaus Energija',
            'service_type' => ServiceType::HEATING,
            'contact_info' => [
                'phone' => '+370 5 239 5555',
                'email' => 'info@ve.lt',
                'website' => 'https://www.ve.lt',
            ],
        ]);
    }
}
