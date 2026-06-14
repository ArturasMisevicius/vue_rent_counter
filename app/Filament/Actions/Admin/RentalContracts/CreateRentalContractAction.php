<?php

namespace App\Filament\Actions\Admin\RentalContracts;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\RentalContracts\RentalContractGuard;
use App\Http\Requests\Admin\RentalContracts\StoreRentalContractRequest;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateRentalContractAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly RentalContractGuard $guard,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): RentalContract
    {
        Gate::forUser($actor)->authorize('create', RentalContract::class);

        $organizationId = $this->resolveOrganizationId($actor, $data);
        $validated = (new StoreRentalContractRequest)
            ->forOrganization($organizationId)
            ->validatePayload($data, $actor);

        $scope = $this->guard->validatePayload($validated, $organizationId);

        return DB::transaction(function () use ($actor, $organizationId, $validated, $scope): RentalContract {
            $contract = RentalContract::query()->create([
                ...$validated,
                'organization_id' => $organizationId,
                'property_assignment_id' => $scope['assignment']?->id,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $contract,
                [
                    'after' => $contract->getAttributes(),
                    'context' => ['mutation' => 'rental_contract.created'],
                ],
                $actor->id,
                'Rental contract created',
            );

            return $contract->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveOrganizationId(User $actor, array $data): int
    {
        $organizationId = $data['organization_id'] ?? $actor->organization_id;

        if (! is_numeric($organizationId)) {
            throw ValidationException::withMessages([
                'organization_id' => __('admin.rental_contracts.messages.invalid_scope'),
            ]);
        }

        if (! $actor->isSuperadmin() && (int) $actor->organization_id !== (int) $organizationId) {
            throw ValidationException::withMessages([
                'organization_id' => __('admin.rental_contracts.messages.invalid_scope'),
            ]);
        }

        return (int) $organizationId;
    }
}
