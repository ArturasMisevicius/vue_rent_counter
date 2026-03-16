<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->name();
        $leaseStart = fake()->dateTimeBetween('-2 years', 'now');
        $leaseEnd = fake()->boolean(70)
            ? fake()->dateTimeBetween('now', '+2 years')
            : null;

        return [
            'tenant_id' => 1,
            'slug' => fake()->unique()->slug(),
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'property_id' => Property::factory(),
            'lease_start' => $leaseStart,
            'lease_end' => $leaseEnd,
        ];
    }

    /**
     * Align tenant and property to a specific tenant_id.
     */
    public function forTenantId(int $tenantId): static
    {
        return $this->state(function ($attributes) use ($tenantId) {
            $property = $attributes['property_id'] ?? Property::factory();

            if ($property instanceof Factory) {
                $property = $property->forTenantId($tenantId);
            }

            return [
                'tenant_id' => $tenantId,
                'property_id' => $property,
            ];
        });
    }

    /**
     * Attach the tenant to a concrete property and sync tenant_id.
     */
    public function forProperty(Property $property): static
    {
        return $this->state(fn ($attributes) => [
            'property_id' => $property->id,
            'tenant_id' => $property->tenant_id,
        ]);
    }

    /**
     * Keep tenant_id consistent with the related property.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Tenant $tenant) {
            $property = $tenant->property ?? Property::find($tenant->property_id);

            if ($property && $tenant->tenant_id !== $property->tenant_id) {
                $tenant->tenant_id = $property->tenant_id;
            }
        })->afterCreating(function (Tenant $tenant) {
            $property = $tenant->property ?? Property::find($tenant->property_id);

            if ($property && $tenant->tenant_id !== $property->tenant_id) {
                $tenant->update(['tenant_id' => $property->tenant_id]);
            }

            if (! $property) {
                return;
            }

            $leaseEnd = $tenant->lease_end
                ? Carbon::parse($tenant->lease_end)
                : null;

            // Null vacated_at for active/future leases to mark current assignment
            $vacatedAt = $leaseEnd && $leaseEnd->isPast()
                ? $leaseEnd
                : null;

            DB::table('property_tenant')->updateOrInsert(
                [
                    'property_id' => $property->id,
                    'tenant_id' => $tenant->id,
                ],
                [
                    'assigned_at' => $tenant->lease_start,
                    'vacated_at' => $vacatedAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });
    }
}
