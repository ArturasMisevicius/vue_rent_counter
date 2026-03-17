<?php

namespace App\Actions\Tenant\Readings;

use App\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Enums\MeterReadingSubmissionMethod;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitTenantReadingAction
{
    public function __construct(
        protected CreateMeterReadingAction $createMeterReadingAction,
    ) {}

    public function handle(
        User $tenant,
        Meter|int $meter,
        string|int|float $readingValue,
        string $readingDate,
        ?string $notes = null,
    ): MeterReading {
        $meter = $meter instanceof Meter
            ? $meter
            : Meter::query()
                ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                ->where('organization_id', $tenant->organization_id)
                ->findOrFail($meter);

        $currentPropertyId = $tenant->currentPropertyAssignment()
            ->select(['id', 'property_id', 'tenant_user_id'])
            ->value('property_id');

        if ($currentPropertyId === null || $meter->property_id !== $currentPropertyId) {
            throw ValidationException::withMessages([
                'meter_id' => 'You may only submit readings for meters assigned to your current property.',
            ]);
        }

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
