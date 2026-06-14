<?php

namespace App\Filament\Support\RentalContracts;

use App\Enums\RentalContractStatus;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RentalContractGuard
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{tenant: User, property: Property, assignment: PropertyAssignment|null}
     */
    public function validatePayload(
        array $data,
        int $organizationId,
        ?RentalContract $ignoredContract = null,
    ): array {
        $tenant = User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'tenant_status', 'portal_access_enabled'])
            ->tenants()
            ->forOrganization($organizationId)
            ->find($data['tenant_id']);

        $property = Property::query()
            ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number', 'type', 'floor_area_sqm'])
            ->forOrganization($organizationId)
            ->find($data['property_id']);

        if (! $tenant instanceof User || ! $property instanceof Property) {
            throw ValidationException::withMessages([
                'tenant_id' => __('admin.rental_contracts.messages.invalid_scope'),
            ]);
        }

        $assignment = $this->assignmentFor($data, $organizationId, $tenant, $property);
        $status = $this->statusFrom($data['status'] ?? null);

        if ($status === RentalContractStatus::ACTIVE) {
            if (! $assignment instanceof PropertyAssignment || $assignment->unassigned_at !== null) {
                throw ValidationException::withMessages([
                    'property_assignment_id' => __('admin.rental_contracts.messages.active_assignment_required'),
                ]);
            }

            if ($this->hasDuplicateActiveContract($organizationId, $tenant, $property, $ignoredContract)) {
                throw ValidationException::withMessages([
                    'status' => __('admin.rental_contracts.messages.duplicate_active'),
                ]);
            }
        }

        return [
            'tenant' => $tenant,
            'property' => $property,
            'assignment' => $assignment,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assignmentFor(
        array $data,
        int $organizationId,
        User $tenant,
        Property $property,
    ): ?PropertyAssignment {
        $query = PropertyAssignment::query()
            ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'assigned_at', 'unassigned_at'])
            ->forOrganization($organizationId)
            ->forTenant((int) $tenant->getKey())
            ->forProperty((int) $property->getKey());

        if (filled($data['property_assignment_id'] ?? null)) {
            return $query->find($data['property_assignment_id']);
        }

        return $query
            ->current()
            ->latestAssignedFirst()
            ->first();
    }

    private function hasDuplicateActiveContract(
        int $organizationId,
        User $tenant,
        Property $property,
        ?RentalContract $ignoredContract,
    ): bool {
        return RentalContract::query()
            ->forOrganization($organizationId)
            ->forTenant((int) $tenant->getKey())
            ->forProperty((int) $property->getKey())
            ->active()
            ->when(
                $ignoredContract instanceof RentalContract,
                fn ($query) => $query->whereKeyNot($ignoredContract->getKey()),
            )
            ->exists();
    }

    private function statusFrom(mixed $status): RentalContractStatus
    {
        if ($status instanceof RentalContractStatus) {
            return $status;
        }

        return RentalContractStatus::from((string) $status);
    }
}
