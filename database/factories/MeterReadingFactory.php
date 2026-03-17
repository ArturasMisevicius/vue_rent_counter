<?php

namespace Database\Factories;

use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterReading>
 */
class MeterReadingFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $property = Property::factory()->for($organization);

        return [
            'organization_id' => $organization,
            'property_id' => $property,
            'meter_id' => Meter::factory()->for($organization)->for($property),
            'submitted_by_user_id' => User::factory()->admin()->for($organization),
            'reading_value' => fake()->randomFloat(3, 1, 10000),
            'reading_date' => now()->subDay()->toDateString(),
            'validation_status' => MeterReadingValidationStatus::VALID,
            'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
            'notes' => null,
        ];
    }
}
