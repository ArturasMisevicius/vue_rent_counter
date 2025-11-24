<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        $street = fake()->streetAddress();

        return [
            'tenant_id' => 1,
            'name' => "{$street} Residence",
            'address' => "{$street}, Vilnius",
            'total_apartments' => fake()->numberBetween(4, 50),
            'gyvatukas_summer_average' => null,
            'gyvatukas_last_calculated' => null,
        ];
    }

    /**
     * Override the tenant the building belongs to.
     */
    public function forTenantId(int $tenantId): static
    {
        return $this->state(fn ($attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }
}
