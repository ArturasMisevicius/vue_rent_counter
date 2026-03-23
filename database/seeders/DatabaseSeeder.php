<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            TranslationSeeder::class,
            SystemSettingSeeder::class,
            IntegrationHealthCheckSeeder::class,
            LoginDemoUsersSeeder::class,
            LegacyReferenceFoundationSeeder::class,
            BalticReferenceLocalizationSeeder::class,
            LegacyOperationsFoundationSeeder::class,
            LegacyPlatformFoundationSeeder::class,
            LegacyCollaborationFoundationSeeder::class,
            OperationalDemoDatasetSeeder::class,
        ]);
    }
}
