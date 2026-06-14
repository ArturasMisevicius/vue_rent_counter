<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantMoveOut;

use App\Enums\AuditLogAction;
use App\Enums\PropertyAssignmentStatus;
use App\Enums\PropertyOccupancyStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;

final class UpdatePropertyOccupancyStatus
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(
        Property $property,
        ?PropertyOccupancyStatus $status = null,
        ?User $actor = null,
        bool $preserveManualHold = true,
    ): Property {
        $currentStatus = $property->occupancyStatus();

        if ($status === null && $preserveManualHold && $currentStatus->isManualHold()) {
            return $property;
        }

        $resolvedStatus = $status ?? $this->deriveStatus($property);

        $property->forceFill([
            'occupancy_status' => $resolvedStatus,
        ])->save();

        $freshProperty = $property->fresh(['currentAssignment.tenant']) ?? $property;

        if ($currentStatus !== $resolvedStatus) {
            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $freshProperty,
                [
                    'context' => ['mutation' => 'property.occupancy_status_changed'],
                    'before' => ['occupancy_status' => $currentStatus->value],
                    'after' => ['occupancy_status' => $resolvedStatus->value],
                ],
                $actor?->id,
                'Property occupancy status changed',
            );
        }

        return $freshProperty;
    }

    private function deriveStatus(Property $property): PropertyOccupancyStatus
    {
        $assignment = $property->currentAssignment()
            ->select(['id', 'property_id', 'status', 'unassigned_at'])
            ->first();

        if (! $assignment instanceof PropertyAssignment) {
            return PropertyOccupancyStatus::VACANT;
        }

        return match ($assignment->status) {
            PropertyAssignmentStatus::SCHEDULED => PropertyOccupancyStatus::MOVE_IN_SCHEDULED,
            PropertyAssignmentStatus::MOVE_OUT_SCHEDULED => PropertyOccupancyStatus::MOVE_OUT_SCHEDULED,
            PropertyAssignmentStatus::ACTIVE => PropertyOccupancyStatus::OCCUPIED,
            default => PropertyOccupancyStatus::VACANT,
        };
    }
}
