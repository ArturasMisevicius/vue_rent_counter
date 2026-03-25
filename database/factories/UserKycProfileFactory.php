<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\KycVerificationStatus;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserKycProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserKycProfile>
 */
class UserKycProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'user_id' => User::factory()->tenant()->for($organization),
            'organization_id' => $organization,
            'full_legal_name' => fake()->name(),
            'birth_date' => fake()->date(),
            'nationality' => fake()->country(),
            'gender' => 'female',
            'marital_status' => 'single',
            'tax_id_number' => fake()->numerify('LT-#########'),
            'social_security_number' => fake()->numerify('############'),
            'facial_recognition_consent' => false,
            'secondary_contact_name' => fake()->name(),
            'secondary_contact_relationship' => 'Sibling',
            'secondary_contact_phone' => fake()->e164PhoneNumber(),
            'secondary_contact_email' => fake()->safeEmail(),
            'tertiary_contact_name' => fake()->name(),
            'tertiary_contact_relationship' => 'Parent',
            'tertiary_contact_phone' => fake()->e164PhoneNumber(),
            'tertiary_contact_email' => fake()->safeEmail(),
            'employer_name' => fake()->company(),
            'employment_position' => fake()->jobTitle(),
            'employment_contract_type' => 'full_time',
            'monthly_income_range' => '2000_2999',
            'iban' => 'LT121000011101001000',
            'swift_bic' => 'HABALT22',
            'bank_name' => fake()->company().' Bank',
            'bank_account_holder_name' => fake()->name(),
            'payment_history_score' => fake()->numberBetween(0, 100),
            'external_credit_bureau_reference' => fake()->bothify('CBR-REF-###'),
            'internal_credit_score' => fake()->numberBetween(300, 850),
            'blacklist_status' => false,
            'verification_status' => KycVerificationStatus::UNVERIFIED,
            'rejection_reason' => null,
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
        ];
    }
}
