<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Properties;

use App\Enums\PropertyAssignmentStatus;
use App\Enums\PropertyOccupancyStatus;
use App\Filament\Actions\Admin\TenantMoveOut\UpdatePropertyOccupancyStatus;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignTenantToPropertyAction
{
    public function handle(
        Property $property,
        User $tenant,
        ?float $unitAreaSqm = null,
        ?\DateTimeInterface $moveInDate = null,
        ?\DateTimeInterface $moveOutDate = null,
        PropertyAssignmentStatus $status = PropertyAssignmentStatus::ACTIVE,
        bool $isPrimary = true,
        ?int $occupantsCount = null,
        ?User $actor = null,
    ): PropertyAssignment {
        if (! $tenant->isTenant() || $tenant->organization_id !== $property->organization_id) {
            throw ValidationException::withMessages([
                'tenant' => __('admin.properties.messages.invalid_tenant'),
            ]);
        }

        if ($status === PropertyAssignmentStatus::ACTIVE && $moveInDate === null) {
            throw ValidationException::withMessages([
                'move_in_date' => __('admin.tenants.messages.move_in_required_for_active_assignment'),
            ]);
        }

        return DB::transaction(function () use ($property, $tenant, $unitAreaSqm, $moveInDate, $moveOutDate, $status, $isPrimary, $occupantsCount, $actor): PropertyAssignment {
            $timestamp = now();
            $moveInDate ??= $timestamp;
            $currentAssignment = $property->currentAssignment()->primary()->first();

            if ($currentAssignment?->tenant_user_id === $tenant->id) {
                $currentAssignment->update([
                    'unit_area_sqm' => $unitAreaSqm,
                    'assigned_at' => $moveInDate,
                    'unassigned_at' => $moveOutDate,
                    'billing_start_date' => $moveInDate,
                    'billing_end_date' => $moveOutDate,
                    'status' => $status,
                    'is_primary' => $isPrimary,
                    'occupants_count' => $occupantsCount,
                    'updated_by_user_id' => $actor?->id,
                ]);

                app(UpdatePropertyOccupancyStatus::class)->handle(
                    $property->fresh() ?? $property,
                    $status === PropertyAssignmentStatus::SCHEDULED
                        ? PropertyOccupancyStatus::MOVE_IN_SCHEDULED
                        : PropertyOccupancyStatus::OCCUPIED,
                    $actor,
                );

                return $currentAssignment->fresh();
            }

            if ($isPrimary && $currentAssignment !== null) {
                throw ValidationException::withMessages([
                    'property_id' => __('admin.properties.messages.active_primary_assignment_blocks_new_tenant'),
                ]);
            }

            PropertyAssignment::query()
                ->where('organization_id', $property->organization_id)
                ->where('tenant_user_id', $tenant->id)
                ->current()
                ->update([
                    'unassigned_at' => $timestamp,
                    'status' => PropertyAssignmentStatus::ENDED,
                    'updated_by_user_id' => $actor?->id,
                    'updated_at' => $timestamp,
                ]);

            $assignment = PropertyAssignment::query()->create([
                'organization_id' => $property->organization_id,
                'property_id' => $property->id,
                'tenant_user_id' => $tenant->id,
                'unit_area_sqm' => $unitAreaSqm,
                'status' => $status,
                'is_primary' => $isPrimary,
                'occupants_count' => $occupantsCount,
                'assigned_at' => $moveInDate,
                'unassigned_at' => $moveOutDate,
                'billing_start_date' => $moveInDate,
                'billing_end_date' => $moveOutDate,
                'created_by_user_id' => $actor?->id,
                'updated_by_user_id' => $actor?->id,
            ]);

            app(UpdatePropertyOccupancyStatus::class)->handle(
                $property->fresh() ?? $property,
                $status === PropertyAssignmentStatus::SCHEDULED
                    ? PropertyOccupancyStatus::MOVE_IN_SCHEDULED
                    : PropertyOccupancyStatus::OCCUPIED,
                $actor,
            );

            return $assignment;
        });
    }
}
