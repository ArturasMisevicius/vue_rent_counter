<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationSetting>
 */
class OrganizationSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'billing_contact_name' => fake()->name(),
            'billing_contact_email' => fake()->safeEmail(),
            'billing_contact_phone' => fake()->phoneNumber(),
            'payment_instructions' => fake()->sentence(),
            'invoice_footer' => fake()->sentence(),
        ];
    }
}
