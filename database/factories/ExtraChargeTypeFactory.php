<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExtraChargeTypeCode;
use App\Models\ExtraChargeType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtraChargeType>
 */
class ExtraChargeTypeFactory extends Factory
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
            'name' => $this->faker->unique()->words(3, true),
            'type' => ExtraChargeTypeCode::ONE_TIME_CHARGE,
            'default_amount' => '25.00',
            'currency' => 'EUR',
            'is_recurring' => false,
            'is_taxable' => false,
            'tenant_visible_by_default' => true,
            'requires_comment' => false,
            'requires_attachment' => false,
            'is_active' => true,
        ];
    }

    public function recurring(): self
    {
        return $this->state([
            'type' => ExtraChargeTypeCode::FIXED_SERVICE,
            'is_recurring' => true,
        ]);
    }

    public function discount(): self
    {
        return $this->state([
            'type' => ExtraChargeTypeCode::DISCOUNT,
            'default_amount' => '-10.00',
        ]);
    }
}
