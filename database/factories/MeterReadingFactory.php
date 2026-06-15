<?php

namespace Database\Factories;

use App\Enums\MeterReadingStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingType;
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
            'billing_period_id' => null,
            'meter_id' => Meter::factory()->for($organization)->for($property),
            'tenant_id' => null,
            'submitted_by_user_id' => User::factory()->admin()->for($organization),
            'reading_value' => fake()->randomFloat(3, 1, 10000),
            'reading_date' => now()->subDay()->toDateString(),
            'previous_value' => null,
            'current_value' => null,
            'consumption' => null,
            'validation_status' => MeterReadingValidationStatus::VALID,
            'status' => MeterReadingStatus::APPROVED,
            'submitted_at' => now(),
            'approved_by_user_id' => null,
            'approved_at' => now(),
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'corrected_by_user_id' => null,
            'correction_reason' => null,
            'tenant_comment' => null,
            'voided_at' => null,
            'submission_method' => MeterReadingSubmissionMethod::ADMIN_MANUAL,
            'reading_type' => MeterReadingType::REGULAR,
            'property_assignment_id' => null,
            'move_out_process_id' => null,
            'invoice_id' => null,
            'notes' => null,
        ];
    }
}
