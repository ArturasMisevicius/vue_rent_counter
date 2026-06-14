<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadSourceType;
use App\Models\LeadSource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadSource>
 */
class LeadSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => 'Aruodas CSV',
            'type' => LeadSourceType::ARUODAS_CSV,
            'description' => fake()->sentence(),
            'source_url' => 'https://www.aruodas.lt',
            'privacy_note' => 'CSV imported for owner outreach with legitimate-interest review.',
            'retention_days' => 180,
            'created_by_user_id' => User::factory()->admin(),
            'imported_at' => null,
        ];
    }
}
