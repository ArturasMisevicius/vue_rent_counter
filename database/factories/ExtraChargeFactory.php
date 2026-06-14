<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExtraChargeStatus;
use App\Models\ExtraCharge;
use App\Models\ExtraChargeType;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtraCharge>
 */
class ExtraChargeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'tenant_id' => User::factory(),
            'property_id' => Property::factory(),
            'billing_period_id' => null,
            'invoice_id' => null,
            'extra_charge_type_id' => ExtraChargeType::factory(),
            'title' => $this->faker->sentence(3),
            'description_for_tenant' => $this->faker->sentence(8),
            'internal_note' => $this->faker->sentence(6),
            'amount' => '25.00',
            'currency' => 'EUR',
            'quantity' => '1.000',
            'unit_price' => '25.0000',
            'tax_amount' => '0.00',
            'total_amount' => '25.00',
            'status' => ExtraChargeStatus::APPROVED,
            'is_recurring' => false,
            'starts_at' => now()->startOfMonth()->toDateString(),
            'ends_at' => now()->endOfMonth()->toDateString(),
            'created_by_user_id' => null,
            'approved_by_user_id' => null,
            'approved_at' => now(),
        ];
    }

    public function recurring(): self
    {
        return $this->state([
            'is_recurring' => true,
            'ends_at' => null,
        ]);
    }

    public function rejected(): self
    {
        return $this->state([
            'status' => ExtraChargeStatus::REJECTED,
        ]);
    }

    public function voided(): self
    {
        return $this->state([
            'status' => ExtraChargeStatus::VOIDED,
        ]);
    }
}
