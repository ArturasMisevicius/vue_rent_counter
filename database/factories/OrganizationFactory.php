<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'status' => OrganizationStatus::ACTIVE,
            'owner_user_id' => null,
        ];
    }

    public function starterShowcase(): static
    {
        return $this->showcaseForPlan(SubscriptionPlan::STARTER);
    }

    public function basicShowcase(): static
    {
        return $this->showcaseForPlan(SubscriptionPlan::BASIC);
    }

    public function professionalShowcase(): static
    {
        return $this->showcaseForPlan(SubscriptionPlan::PROFESSIONAL);
    }

    public function enterpriseShowcase(): static
    {
        return $this->showcaseForPlan(SubscriptionPlan::ENTERPRISE);
    }

    public function customShowcase(): static
    {
        return $this->showcaseForPlan(SubscriptionPlan::CUSTOM);
    }

    private function showcaseForPlan(SubscriptionPlan $plan): static
    {
        return $this->state([
            'name' => Str::headline($plan->value).' Showcase Organization',
            'slug' => 'showcase-'.$plan->value,
            'status' => OrganizationStatus::ACTIVE,
        ]);
    }
}
