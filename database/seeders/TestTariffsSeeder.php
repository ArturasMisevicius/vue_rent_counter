<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestTariffsSeeder extends Seeder
{
    /**
     * Seed test tariffs for all Lithuanian utility providers.
     */
    public function run(): void
    {
        $this->call(TariffsSeeder::class);
    }
}
