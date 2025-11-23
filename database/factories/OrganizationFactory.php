<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();
        $plan = fake()->randomElement(['basic', 'professional', 'enterprise']);

        return [
            'name' => $name,
            'slug' => null,
            'domain' => fn (array $attributes) => Str::slug($attributes['name']) . '.example.com',
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
            'plan' => $plan,
            'max_properties' => $plan === 'enterprise' ? 999 : ($plan === 'professional' ? 250 : 100),
            'max_users' => $plan === 'enterprise' ? 500 : ($plan === 'professional' ? 150 : 50),
            'trial_ends_at' => now()->addDays(14),
            'subscription_ends_at' => now()->addMonths(6),
            'settings' => [
                'invoice_prefix' => strtoupper(Str::substr($name, 0, 3)),
                'invoice_number_start' => fake()->numberBetween(1000, 5000),
                'enable_notifications' => true,
            ],
            'features' => [
                'advanced_reporting' => $plan !== 'basic',
                'api_access' => $plan === 'enterprise',
                'custom_branding' => $plan === 'enterprise',
                'audit_logs' => $plan !== 'basic',
            ],
            'timezone' => 'Europe/Vilnius',
            'locale' => 'lt',
            'currency' => 'EUR',
            'created_by' => null,
            'last_activity_at' => now()->subDay(),
        ];
    }

    /**
     * Mark the organization as suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => 'Suspended for non-payment',
        ]);
    }

    /**
     * Attach a creator user to the organization.
     */
    public function withCreator(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user?->id ?? User::factory()->admin()->create()->id,
        ]);
    }
}
