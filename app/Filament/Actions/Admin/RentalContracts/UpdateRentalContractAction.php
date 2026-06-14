<?php

namespace App\Filament\Actions\Admin\RentalContracts;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\RentalContracts\RentalContractGuard;
use App\Http\Requests\Admin\RentalContracts\UpdateRentalContractRequest;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateRentalContractAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly RentalContractGuard $guard,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(RentalContract $contract, User $actor, array $data): RentalContract
    {
        Gate::forUser($actor)->authorize('update', $contract);

        $validated = (new UpdateRentalContractRequest)
            ->forOrganization((int) $contract->organization_id)
            ->ignoreContract($contract)
            ->validatePayload([
                ...$contract->only([
                    'tenant_id',
                    'property_id',
                    'property_assignment_id',
                    'contract_number',
                    'status',
                    'start_date',
                    'end_date',
                    'signed_date',
                    'rent_amount',
                    'deposit_amount',
                    'currency',
                    'tenant_visible',
                    'internal_notes',
                    'tenant_visible_notes',
                ]),
                ...$data,
            ], $actor);

        $scope = $this->guard->validatePayload($validated, (int) $contract->organization_id, $contract);

        return DB::transaction(function () use ($actor, $contract, $validated, $scope): RentalContract {
            $before = $contract->getOriginal();

            $contract->fill([
                ...Arr::only($validated, [
                    'tenant_id',
                    'property_id',
                    'property_assignment_id',
                    'contract_number',
                    'status',
                    'start_date',
                    'end_date',
                    'signed_date',
                    'rent_amount',
                    'deposit_amount',
                    'currency',
                    'tenant_visible',
                    'internal_notes',
                    'tenant_visible_notes',
                ]),
                'property_assignment_id' => $scope['assignment']?->id,
                'updated_by_user_id' => $actor->id,
            ]);
            $contract->save();

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $contract,
                [
                    'before' => $before,
                    'after' => $contract->getAttributes(),
                    'context' => ['mutation' => 'rental_contract.updated'],
                ],
                $actor->id,
                'Rental contract updated',
            );

            return $contract->refresh();
        });
    }
}
