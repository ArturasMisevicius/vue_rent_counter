<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantMoveOut;

use App\Enums\AuditLogAction;
use App\Enums\MoveOutProcessStatus;
use App\Enums\PortalAccessAfterMoveOut;
use App\Enums\PropertyAssignmentStatus;
use App\Enums\RentalContractStatus;
use App\Enums\TenantStatus;
use App\Filament\Actions\Admin\TenantMoveOut\Concerns\AuthorizesTenantMoveOut;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\MoveOutProcess;
use App\Models\PropertyAssignment;
use App\Models\RentalContract;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ScheduleTenantMoveOut
{
    use AuthorizesTenantMoveOut;

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{
     *     move_out_date?: string,
     *     reason?: string|null,
     *     internal_note?: string|null,
     *     final_readings_required?: bool,
     *     portal_access_after_move_out?: string|PortalAccessAfterMoveOut|null
     * }  $data
     */
    public function handle(User $actor, PropertyAssignment $assignment, array $data): MoveOutProcess
    {
        $this->authorizeTenantMoveOut($actor, (int) $assignment->organization_id);
        $moveOutDate = $this->moveOutDate($assignment, $data['move_out_date'] ?? null);
        $portalPolicy = $this->portalPolicy($data['portal_access_after_move_out'] ?? null);

        $assignment->loadMissing(['tenant:id,organization_id,role,tenant_status', 'property:id,organization_id']);

        if ($assignment->status !== PropertyAssignmentStatus::ACTIVE || $assignment->unassigned_at !== null) {
            throw ValidationException::withMessages([
                'assignment' => __('admin.move_out.messages.active_assignment_required'),
            ]);
        }

        return DB::transaction(function () use ($actor, $assignment, $data, $moveOutDate, $portalPolicy): MoveOutProcess {
            $contract = $this->activeContract($assignment);

            if ($assignment->moveOutProcesses()->open()->exists()) {
                throw ValidationException::withMessages([
                    'assignment' => __('admin.move_out.messages.open_process_exists'),
                ]);
            }

            $process = MoveOutProcess::query()->create([
                'organization_id' => $assignment->organization_id,
                'tenant_id' => $assignment->tenant_user_id,
                'property_id' => $assignment->property_id,
                'property_assignment_id' => $assignment->id,
                'status' => MoveOutProcessStatus::SCHEDULED,
                'move_out_date' => $moveOutDate->toDateString(),
                'final_readings_required' => (bool) ($data['final_readings_required'] ?? true),
                'contract_id' => $contract?->id,
                'portal_access_after_move_out' => $portalPolicy,
                'reason' => filled($data['reason'] ?? null) ? trim((string) $data['reason']) : null,
                'internal_note' => filled($data['internal_note'] ?? null) ? trim((string) $data['internal_note']) : null,
                'started_by_user_id' => $actor->id,
            ]);

            $beforeAssignment = $assignment->getOriginal();

            $assignment->forceFill([
                'status' => PropertyAssignmentStatus::MOVE_OUT_SCHEDULED,
                'move_out_date' => $moveOutDate->toDateString(),
                'billing_end_date' => $moveOutDate->toDateString(),
                'move_out_reason' => filled($data['reason'] ?? null) ? trim((string) $data['reason']) : null,
                'move_out_scheduled_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ])->save();

            $assignment->tenant?->forceFill([
                'tenant_status' => TenantStatus::MOVE_OUT_SCHEDULED,
            ])->save();

            app(UpdatePropertyOccupancyStatus::class)->handle(
                $assignment->property()->firstOrFail(),
                actor: $actor,
                preserveManualHold: false,
            );

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $process,
                [
                    'context' => ['mutation' => 'tenant_move_out.scheduled'],
                    'assignment_id' => $assignment->id,
                    'tenant_id' => $assignment->tenant_user_id,
                    'property_id' => $assignment->property_id,
                    'move_out_date' => $moveOutDate->toDateString(),
                ],
                $actor->id,
                'Tenant move-out scheduled',
            );

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $assignment,
                [
                    'context' => ['mutation' => 'tenant_property_assignment.move_out_scheduled'],
                    'before' => $beforeAssignment,
                    'after' => $assignment->getAttributes(),
                ],
                $actor->id,
                'Tenant property assignment move-out scheduled',
            );

            return $process->refresh();
        });
    }

    private function moveOutDate(PropertyAssignment $assignment, mixed $date): CarbonImmutable
    {
        if (! is_string($date) || blank($date)) {
            throw ValidationException::withMessages([
                'move_out_date' => __('admin.move_out.messages.move_out_date_required'),
            ]);
        }

        $moveOutDate = CarbonImmutable::parse($date)->startOfDay();

        if ($assignment->assigned_at !== null && $moveOutDate->lt(CarbonImmutable::parse($assignment->assigned_at)->startOfDay())) {
            throw ValidationException::withMessages([
                'move_out_date' => __('admin.move_out.messages.move_out_date_after_move_in'),
            ]);
        }

        return $moveOutDate;
    }

    private function portalPolicy(mixed $value): PortalAccessAfterMoveOut
    {
        if ($value instanceof PortalAccessAfterMoveOut) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return PortalAccessAfterMoveOut::from($value);
        }

        return PortalAccessAfterMoveOut::KEEP_HISTORICAL_ACCESS;
    }

    private function activeContract(PropertyAssignment $assignment): ?RentalContract
    {
        return RentalContract::query()
            ->select(['id', 'organization_id', 'tenant_id', 'property_id', 'property_assignment_id', 'status'])
            ->forOrganization((int) $assignment->organization_id)
            ->where('tenant_id', $assignment->tenant_user_id)
            ->where('property_id', $assignment->property_id)
            ->where(function ($query) use ($assignment): void {
                $query
                    ->whereNull('property_assignment_id')
                    ->orWhere('property_assignment_id', $assignment->id);
            })
            ->where('status', RentalContractStatus::ACTIVE)
            ->first();
    }
}
