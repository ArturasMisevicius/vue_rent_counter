<?php

namespace App\Filament\Actions\Admin\RentalContracts;

use App\Enums\AuditLogAction;
use App\Enums\RentalContractStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TerminateRentalContractAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(RentalContract $contract, User $actor, string $reason): RentalContract
    {
        Gate::forUser($actor)->authorize('terminate', $contract);

        if (blank($reason)) {
            throw ValidationException::withMessages([
                'termination_reason' => __('admin.rental_contracts.messages.termination_reason_required'),
            ]);
        }

        if (! $contract->canBeTerminated()) {
            throw ValidationException::withMessages([
                'status' => __('admin.rental_contracts.messages.cannot_terminate'),
            ]);
        }

        return DB::transaction(function () use ($actor, $contract, $reason): RentalContract {
            $before = $contract->getOriginal();

            $contract->forceFill([
                'status' => RentalContractStatus::TERMINATED,
                'terminated_at' => now(),
                'termination_reason' => trim($reason),
                'updated_by_user_id' => $actor->id,
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $contract,
                [
                    'before' => $before,
                    'after' => $contract->getAttributes(),
                    'context' => ['mutation' => 'rental_contract.terminated'],
                ],
                $actor->id,
                'Rental contract terminated',
            );

            return $contract->refresh();
        });
    }
}
