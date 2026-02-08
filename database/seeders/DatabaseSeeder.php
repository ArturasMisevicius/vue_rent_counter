<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            ProvidersSeeder::class,
            TariffsSeeder::class,
            GoldMasterSeeder::class, // Gold Master hierarchy for testing Truth-but-Verify workflow
            FaqSeeder::class,
        ]);
    }
}
