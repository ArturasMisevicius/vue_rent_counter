<?php

namespace Database\Factories;

use App\Enums\SystemSubscriptionPlan;
use App\Enums\SystemTenantStatus;
use App\Models\SystemTenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemTenant>
 */
class SystemTenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'primary_contact_email' => $this->faker->unique()->safeEmail(),
            'subscription_plan' => $this->faker->randomElement(SystemSubscriptionPlan::cases()),
            'status' => $this->faker->randomElement(SystemTenantStatus::cases()),
            'domain' => $this->faker->optional()->domainName(),
            'settings' => $this->faker->optional()->randomElements([
                'feature_x_enabled' => $this->faker->boolean(),
                'theme' => $this->faker->randomElement(['light', 'dark']),
                'notifications_enabled' => $this->faker->boolean(),
            ]),
            'billing_info' => $this->faker->optional()->randomElements([
                'billing_email' => $this->faker->email(),
                'payment_method' => $this->faker->randomElement(['credit_card', 'bank_transfer']),
                'billing_address' => $this->faker->address(),
            ]),
        ];
    }
}
