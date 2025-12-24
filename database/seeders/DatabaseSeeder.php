<?php

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
            GoldMasterSeeder::class, // Gold Master hierarchy for testing Truth-but-Verify workflow
            FaqSeeder::class,
        ]);
    }
}
