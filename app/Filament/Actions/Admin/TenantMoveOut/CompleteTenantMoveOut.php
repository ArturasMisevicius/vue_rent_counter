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

final class CompleteTenantMoveOut
{
    use AuthorizesTenantMoveOut;

    public function __construct(
        private readonly CloseRentalContractForMoveOut $closeRentalContractForMoveOut,
        private readonly UpdateTenantPortalAccessAfterMoveOut $updateTenantPortalAccessAfterMoveOut,
        private readonly UpdatePropertyOccupancyStatus $updatePropertyOccupancyStatus,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{allow_without_final_invoice?: bool, allow_without_final_readings?: bool}  $data
     */
    public function handle(User $actor, MoveOutProcess $process, array $data = []): MoveOutProcess
    {
        $this->authorizeTenantMoveOut($actor, (int) $process->organization_id);
        $this->guardCompletion($process, $data);

        return DB::transaction(function () use ($actor, $process): MoveOutProcess {
            $process->loadMissing([
                'property:id,organization_id,occupancy_status',
                'propertyAssignment:id,organization_id,property_id,tenant_user_id,status,unassigned_at,move_out_date,billing_end_date,move_out_completed_at',
                'tenant:id,organization_id,tenant_status,portal_access_enabled',
                'contract:id,organization_id,tenant_id,property_id,property_assignment_id,status,end_date,terminated_at,termination_reason',
            ]);

            $assignment = $process->propertyAssignment;

            if (! $assignment instanceof PropertyAssignment) {
                throw ValidationException::withMessages([
                    'assignment' => __('admin.move_out.messages.assignment_required'),
                ]);
            }

            $beforeAssignment = $assignment->getOriginal();
            $assignment->forceFill([
                'status' => PropertyAssignmentStatus::ENDED,
                'unassigned_at' => $process->move_out_date?->endOfDay(),
                'move_out_date' => $process->move_out_date?->toDateString(),
                'billing_end_date' => $process->move_out_date?->toDateString(),
                'move_out_completed_by_user_id' => $actor->id,
                'move_out_completed_at' => now(),
                'updated_by_user_id' => $actor->id,
            ])->save();

            $this->closeRentalContractForMoveOut->handle($process, $actor);

            $tenant = $process->tenant;

            if ($tenant instanceof User) {
                $tenant->forceFill([
                    'tenant_status' => $this->tenantStatusAfterMoveOut($tenant, (int) $assignment->id),
                ])->save();

                $this->updateTenantPortalAccessAfterMoveOut->handle($process, $actor);
            }

            if ($process->property !== null) {
                $this->updatePropertyOccupancyStatus->handle($process->property, actor: $actor, preserveManualHold: false);
            }

            $beforeProcess = $process->getOriginal();
            $process->forceFill([
                'status' => MoveOutProcessStatus::COMPLETED,
                'completed_by_user_id' => $actor->id,
                'completed_at' => now(),
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $assignment,
                [
                    'context' => ['mutation' => 'tenant_move_out.assignment_ended'],
                    'move_out_process_id' => $process->id,
                    'before' => $beforeAssignment,
                    'after' => $assignment->getAttributes(),
                ],
                $actor->id,
                'Tenant property assignment ended for move-out',
            );

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $process,
                [
                    'context' => ['mutation' => 'tenant_move_out.completed'],
                    'before' => $beforeProcess,
                    'after' => $process->getAttributes(),
                ],
                $actor->id,
                'Tenant move-out completed',
            );

            return $process->refresh();
        });
    }

    /**
     * @param  array{allow_without_final_invoice?: bool, allow_without_final_readings?: bool}  $data
     */
    private function guardCompletion(MoveOutProcess $process, array $data): void
    {
        if (! $process->status instanceof MoveOutProcessStatus || ! $process->status->isOpen()) {
            throw ValidationException::withMessages([
                'move_out_process' => __('admin.move_out.messages.process_not_open'),
            ]);
        }

        if (
            (bool) $process->final_readings_required
            && $process->final_readings_completed_at === null
            && ! (bool) ($data['allow_without_final_readings'] ?? false)
        ) {
            throw ValidationException::withMessages([
                'final_readings' => __('admin.move_out.messages.final_readings_before_completion'),
            ]);
        }

        if ($process->final_invoice_id === null && ! (bool) ($data['allow_without_final_invoice'] ?? false)) {
            throw ValidationException::withMessages([
                'final_invoice' => __('admin.move_out.messages.final_invoice_before_completion'),
            ]);
        }
    }

    private function tenantStatusAfterMoveOut(User $tenant, int $completedAssignmentId): TenantStatus
    {
        $openAssignments = $tenant->propertyAssignments()
            ->select(['id', 'tenant_user_id', 'status', 'unassigned_at'])
            ->current()
            ->whereKeyNot($completedAssignmentId)
            ->get();

        if ($openAssignments->contains(fn (PropertyAssignment $assignment): bool => $assignment->status === PropertyAssignmentStatus::ACTIVE)) {
            return TenantStatus::ACTIVE;
        }

        if ($openAssignments->contains(fn (PropertyAssignment $assignment): bool => $assignment->status === PropertyAssignmentStatus::MOVE_OUT_SCHEDULED)) {
            return TenantStatus::MOVE_OUT_SCHEDULED;
        }

        return TenantStatus::MOVED_OUT;
    }
}
