<?php

declare(strict_types=1);

namespace App\Filament\Actions\Tenant\Readings;

use App\Enums\MeterReadingSubmissionMethod;
use App\Filament\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Http\Requests\Tenant\StoreMeterReadingRequest;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class SubmitTenantReadingAction
{
    public function __construct(
        protected CreateMeterReadingAction $createMeterReadingAction,
        protected WorkspaceResolver $workspaceResolver,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function handle(
        User $tenant,
        string|int $meterId,
        string|int|float $readingValue,
        string $readingDate,
        ?string $notes = null,
    ): MeterReading {
        $workspace = $this->workspaceResolver->resolveFor($tenant);

        if (! $workspace->isTenant() || $workspace->organizationId === null || $workspace->propertyId === null) {
            throw new AuthorizationException(__('tenant.pages.readings.unauthorized_meter'));
        }

        $validated = $this->validatePayload(
            tenant: $tenant,
            meterId: $meterId,
            readingValue: $readingValue,
            readingDate: $readingDate,
            notes: $notes,
        );

        $meter = Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
            ->forOrganization($workspace->organizationId)
            ->forProperty($workspace->propertyId)
            ->with([
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.currentAssignment' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'assigned_at', 'unassigned_at'])
                    ->forOrganization($workspace->organizationId)
                    ->current(),
            ])
            ->whereKey((int) $validated['meterId'])
            ->first();

        if ($meter === null) {
            throw new AuthorizationException(__('tenant.pages.readings.unauthorized_meter'));
        }

        Gate::forUser($tenant)->authorize('view', $meter);

        return $this->createMeterReadingAction->handle(
            meter: $meter,
            readingValue: $validated['readingValue'],
            readingDate: $validated['readingDate'],
            submittedBy: $tenant,
            submissionMethod: MeterReadingSubmissionMethod::TENANT_PORTAL,
            notes: filled($validated['notes']) ? $validated['notes'] : null,
        );
    }

    /**
     * @return array{
     *     meterId: int,
     *     readingValue: string|int|float,
     *     readingDate: string,
     *     notes: string|null
     * }
     */
    private function validatePayload(
        User $tenant,
        string|int $meterId,
        string|int|float $readingValue,
        string $readingDate,
        ?string $notes,
    ): array {
        /** @var StoreMeterReadingRequest $request */
        $request = new StoreMeterReadingRequest;

        return $request->validatePayload([
            'meterId' => $meterId,
            'readingValue' => $readingValue,
            'readingDate' => $readingDate,
            'notes' => $notes,
        ], $tenant);
    }
}
