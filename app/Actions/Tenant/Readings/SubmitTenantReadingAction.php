<?php

namespace App\Actions\Tenant\Readings;

use App\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Enums\MeterReadingSubmissionMethod;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class SubmitTenantReadingAction
{
    public function __construct(
        protected CreateMeterReadingAction $createMeterReadingAction,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function handle(
        User $tenant,
        int $meterId,
        string|int|float $readingValue,
        string $readingDate,
        ?string $notes = null,
    ): MeterReading {
        $meter = Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
            ->with([
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.currentAssignment:id,property_id,tenant_user_id,assigned_at,unassigned_at',
            ])
            ->where('organization_id', $tenant->organization_id)
            ->findOrFail($meterId);

        Gate::forUser($tenant)->authorize('view', $meter);

        return $this->createMeterReadingAction->handle(
            meter: $meter,
            readingValue: $readingValue,
            readingDate: $readingDate,
            submittedBy: $tenant,
            submissionMethod: MeterReadingSubmissionMethod::TENANT_PORTAL,
            notes: $notes,
        );
    }
}
