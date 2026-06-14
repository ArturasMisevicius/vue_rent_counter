<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MoveOutProcessStatus;
use App\Enums\PortalAccessAfterMoveOut;
use App\Models\MoveOutProcess;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoveOutProcess>
 */
class MoveOutProcessFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();
        $tenant = User::factory()->tenant()->for($organization);
        $property = Property::factory()->for($organization);

        return [
            'organization_id' => $organization,
            'tenant_id' => $tenant,
            'property_id' => $property,
            'property_assignment_id' => PropertyAssignment::factory()
                ->for($organization)
                ->for($property)
                ->for($tenant, 'tenant'),
            'status' => MoveOutProcessStatus::SCHEDULED,
            'move_out_date' => today()->addMonth(),
            'final_readings_required' => true,
            'final_readings_completed_at' => null,
            'final_invoice_id' => null,
            'contract_id' => null,
            'portal_access_after_move_out' => PortalAccessAfterMoveOut::KEEP_HISTORICAL_ACCESS,
            'reason' => fake()->sentence(),
            'internal_note' => null,
            'started_by_user_id' => null,
            'completed_by_user_id' => null,
            'completed_at' => null,
        ];
    }
}
