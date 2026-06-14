<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantMoveOut;

use App\Enums\AuditLogAction;
use App\Enums\MoveOutProcessStatus;
use App\Enums\PropertyAssignmentStatus;
use App\Enums\TenantStatus;
use App\Filament\Actions\Admin\TenantMoveOut\Concerns\AuthorizesTenantMoveOut;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\MoveOutProcess;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelTenantMoveOut
{
    use AuthorizesTenantMoveOut;

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly UpdatePropertyOccupancyStatus $updatePropertyOccupancyStatus,
    ) {}

    public function handle(User $actor, MoveOutProcess $process, ?string $reason = null): MoveOutProcess
    {
        $this->authorizeTenantMoveOut($actor, (int) $process->organization_id);

        if (! $process->status instanceof MoveOutProcessStatus || ! $process->status->isOpen()) {
            throw ValidationException::withMessages([
                'move_out_process' => __('admin.move_out.messages.process_not_open'),
            ]);
        }

        return DB::transaction(function () use ($actor, $process, $reason): MoveOutProcess {
            $process->loadMissing([
                'property:id,organization_id,occupancy_status',
                'propertyAssignment:id,organization_id,property_id,tenant_user_id,status,unassigned_at,move_out_date,billing_end_date,move_out_reason',
                'tenant:id,organization_id,tenant_status',
            ]);

            $beforeProcess = $process->getOriginal();
            $process->forceFill([
                'status' => MoveOutProcessStatus::CANCELLED,
                'internal_note' => filled($reason)
                    ? trim((string) $reason)
                    : $process->internal_note,
            ])->save();

            $assignment = $process->propertyAssignment;

            if ($assignment instanceof PropertyAssignment && $assignment->status === PropertyAssignmentStatus::MOVE_OUT_SCHEDULED) {
                $beforeAssignment = $assignment->getOriginal();

                $assignment->forceFill([
                    'status' => PropertyAssignmentStatus::ACTIVE,
                    'move_out_date' => null,
                    'billing_end_date' => null,
                    'move_out_reason' => null,
                    'updated_by_user_id' => $actor->id,
                ])->save();

                $this->auditLogger->record(
                    AuditLogAction::UPDATED,
                    $assignment,
                    [
                        'context' => ['mutation' => 'tenant_move_out.assignment_restored'],
                        'move_out_process_id' => $process->id,
                        'before' => $beforeAssignment,
                        'after' => $assignment->getAttributes(),
                    ],
                    $actor->id,
                    'Tenant property assignment restored after move-out cancellation',
                );
            }

            $process->tenant?->forceFill([
                'tenant_status' => TenantStatus::ACTIVE,
            ])->save();

            if ($process->property !== null) {
                $this->updatePropertyOccupancyStatus->handle($process->property, actor: $actor, preserveManualHold: false);
            }

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $process,
                [
                    'context' => ['mutation' => 'tenant_move_out.cancelled'],
                    'before' => $beforeProcess,
                    'after' => $process->getAttributes(),
                ],
                $actor->id,
                'Tenant move-out cancelled',
            );

            return $process->refresh();
        });
    }
}
