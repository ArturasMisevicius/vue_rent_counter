<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\TenantMoveOut;

use App\Enums\AuditLogAction;
use App\Enums\RentalContractStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\MoveOutProcess;
use App\Models\RentalContract;
use App\Models\User;

final class CloseRentalContractForMoveOut
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(MoveOutProcess $process, User $actor): ?RentalContract
    {
        $contract = $process->contract()
            ->select([
                'id',
                'organization_id',
                'tenant_id',
                'property_id',
                'property_assignment_id',
                'status',
                'end_date',
                'terminated_at',
                'termination_reason',
                'updated_by_user_id',
            ])
            ->first();

        if (! $contract instanceof RentalContract || ! $contract->canBeTerminated()) {
            return $contract;
        }

        $before = $contract->getOriginal();

        $contract->forceFill([
            'status' => RentalContractStatus::TERMINATED,
            'end_date' => $process->move_out_date?->toDateString(),
            'terminated_at' => now(),
            'termination_reason' => $process->reason ?: __('admin.move_out.messages.contract_closed_for_move_out'),
            'updated_by_user_id' => $actor->id,
        ])->save();

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $contract,
            [
                'context' => ['mutation' => 'tenant_move_out.contract_closed'],
                'move_out_process_id' => $process->id,
                'before' => $before,
                'after' => $contract->getAttributes(),
            ],
            $actor->id,
            'Rental contract closed for tenant move-out',
        );

        return $contract->fresh();
    }
}
