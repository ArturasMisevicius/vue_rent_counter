<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\Tariff;
use Illuminate\Database\Seeder;

class TariffsSeeder extends Seeder
{
    /**
     * Seed baseline tariffs for admin/superadmin tariff management pages.
     */
    public function run(): void
    {
        $activeFrom = now()->subYear()->startOfDay();

        $ignitis = Provider::query()->where('name', 'Ignitis')->first();
        if ($ignitis !== null) {
            Tariff::query()->updateOrCreate(
                [
                    'provider_id' => $ignitis->id,
                    'name' => 'Ignitis Standard Time-of-Use',
                ],
                [
                    'configuration' => [
                        'type' => 'time_of_use',
                        'currency' => 'EUR',
                        'zones' => [
                            [
                                'id' => 'day',
                                'start' => '07:00',
                                'end' => '23:00',
                                'rate' => 0.18,
                            ],
                            [
                                'id' => 'night',
                                'start' => '23:00',
                                'end' => '07:00',
                                'rate' => 0.10,
                            ],
                        ],
                        'weekend_logic' => 'apply_night_rate',
                        'fixed_fee' => 0.00,
                    ],
                    'active_from' => $activeFrom,
                    'active_until' => null,
                ],
            );
        }

        $vilniausVandenys = Provider::query()->where('name', 'Vilniaus Vandenys')->first();
        if ($vilniausVandenys !== null) {
            Tariff::query()->updateOrCreate(
                [
                    'provider_id' => $vilniausVandenys->id,
                    'name' => 'VV Standard Water Rates',
                ],
                [
                    'configuration' => [
                        'type' => 'flat',
                        'currency' => 'EUR',
                        'supply_rate' => 0.97,
                        'sewage_rate' => 1.23,
                        'fixed_fee' => 0.85,
                    ],
                    'active_from' => $activeFrom,
                    'active_until' => null,
                ],
            );
        }

        $vilniausEnergija = Provider::query()->where('name', 'Vilniaus Energija')->first();
        if ($vilniausEnergija !== null) {
            Tariff::query()->updateOrCreate(
                [
                    'provider_id' => $vilniausEnergija->id,
                    'name' => 'VE Heating Standard',
                ],
                [
                    'configuration' => [
                        'type' => 'flat',
                        'currency' => 'EUR',
                        'rate' => 0.065,
                        'fixed_fee' => 0.00,
                    ],
                    'active_from' => $activeFrom,
                    'active_until' => null,
                ],
            );
        }
    }
}
