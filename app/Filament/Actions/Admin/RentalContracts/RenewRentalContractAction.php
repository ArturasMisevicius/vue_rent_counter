<?php

namespace App\Filament\Actions\Admin\RentalContracts;

use App\Enums\AuditLogAction;
use App\Enums\RentalContractStatus;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\RentalContract;
use App\Models\User;
use App\Notifications\RentalContracts\RentalContractRenewedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RenewRentalContractAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly CreateRentalContractAction $createRentalContract,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(RentalContract $contract, User $actor, array $data): RentalContract
    {
        Gate::forUser($actor)->authorize('renew', $contract);

        if (! $contract->canBeRenewed()) {
            throw ValidationException::withMessages([
                'status' => __('admin.rental_contracts.messages.cannot_renew'),
            ]);
        }

        return DB::transaction(function () use ($actor, $contract, $data): RentalContract {
            $before = $contract->getOriginal();

            $contract->forceFill([
                'status' => RentalContractStatus::RENEWED,
                'updated_by_user_id' => $actor->id,
            ])->save();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $contract,
                [
                    'before' => $before,
                    'after' => $contract->getAttributes(),
                    'context' => ['mutation' => 'rental_contract.renewed_from'],
                ],
                $actor->id,
                'Rental contract marked renewed',
            );

            $renewed = $this->createRentalContract->handle($actor, [
                'organization_id' => $contract->organization_id,
                'tenant_id' => $contract->tenant_id,
                'property_id' => $contract->property_id,
                'property_assignment_id' => $contract->property_assignment_id,
                'status' => RentalContractStatus::ACTIVE->value,
                'rent_amount' => $contract->rent_amount,
                'deposit_amount' => $contract->deposit_amount,
                'currency' => $contract->currency,
                'tenant_visible' => $contract->tenant_visible,
                'internal_notes' => $contract->internal_notes,
                'tenant_visible_notes' => $contract->tenant_visible_notes,
                ...$data,
                'renewed_from_contract_id' => $contract->id,
            ]);

            $renewed->forceFill(['renewed_from_contract_id' => $contract->id])->save();

            if ($renewed->tenant_visible && $renewed->tenant instanceof User) {
                $renewed->tenant->notify(new RentalContractRenewedNotification($renewed));
            }

            return $renewed->refresh();
        });
    }
}
