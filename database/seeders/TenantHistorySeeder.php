<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class TenantHistorySeeder extends Seeder
{
    private const HISTORY_PER_TENANT = 100;

    /**
     * Seed historical tenant assignments for each tenant_id.
     */
    public function run(): void
    {
        fake()->unique(true);

        $propertiesByTenant = Property::all()->groupBy('tenant_id');

        foreach ($propertiesByTenant as $tenantId => $properties) {
            $this->seedHistoryForTenant((int) $tenantId, $properties);
        }
    }

    /**
     * Create historical tenants and attach them to properties.
     */
    private function seedHistoryForTenant(int $tenantId, Collection $properties): void
    {
        if ($properties->isEmpty()) {
            return;
        }

        $start = Carbon::now()->subMonths(3);
        $nextVacateDates = $properties->mapWithKeys(fn (Property $property) => [
            $property->id => (clone $start)->subMonths(fake()->numberBetween(0, 6)),
        ]);

        for ($i = 0; $i < self::HISTORY_PER_TENANT; $i++) {
            $property = $properties[$i % $properties->count()];

            $leaseEnd = clone $nextVacateDates[$property->id];
            $leaseStart = (clone $leaseEnd)->subMonths(fake()->numberBetween(2, 6));

            $tenant = Tenant::factory()
                ->forProperty($property)
                ->create([
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'lease_start' => $leaseStart,
                    'lease_end' => $leaseEnd,
                ]);

            // Move further back in time to avoid overlapping assignments
            $nextVacateDates[$property->id] = (clone $leaseStart)
                ->subWeeks(fake()->numberBetween(1, 4));
        }
    }
}
