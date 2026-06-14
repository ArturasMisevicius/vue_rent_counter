<?php

declare(strict_types=1);

namespace App\Filament\Actions\Tenant\Readings;

use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\TenantStatus;
use App\Filament\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Filament\Support\Admin\ReadingValidation\ValidateReadingValue;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Http\Requests\Tenant\StoreMeterReadingRequest;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitTenantReadingAction
{
    public function __construct(
        protected CreateMeterReadingAction $createMeterReadingAction,
        protected ValidateReadingValue $validateReadingValue,
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
        string|int|null $invoiceId = null,
    ): MeterReading {
        if ($tenant->tenant_status === TenantStatus::MOVED_OUT) {
            throw new AuthorizationException(__('tenant.pages.readings.move_out_submissions_disabled'));
        }

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

        $invoice = $this->readingRequestInvoice(
            invoiceId: $invoiceId,
            organizationId: $workspace->organizationId,
            propertyId: $workspace->propertyId,
            tenantId: $tenant->id,
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

        if ($invoice instanceof Invoice) {
            return DB::transaction(function () use ($invoice, $meter, $tenant, $validated): MeterReading {
                $lockedInvoice = $this->readingRequestInvoiceQuery(
                    invoiceId: (int) $invoice->id,
                    organizationId: (int) $invoice->organization_id,
                    propertyId: (int) $invoice->property_id,
                    tenantId: $tenant->id,
                )
                    ->lockForUpdate()
                    ->first();

                if (! $lockedInvoice instanceof Invoice) {
                    throw ValidationException::withMessages([
                        'readingValue' => __('tenant.pages.readings.invoice_request_unavailable'),
                    ]);
                }

                $this->ensureMeterIsRequestedForInvoice($meter, $lockedInvoice);
                $this->ensureReadingValuePassesBaseValidation($meter, $validated);
                $this->ensureTenantHasNotSubmittedReadingForInvoicePeriod(
                    tenant: $tenant,
                    meter: $meter,
                    invoice: $lockedInvoice,
                    readingDate: (string) $validated['readingDate'],
                );

                return $this->createMeterReadingAction->handle(
                    meter: $meter,
                    readingValue: $validated['readingValue'],
                    readingDate: $validated['readingDate'],
                    submittedBy: $tenant,
                    submissionMethod: MeterReadingSubmissionMethod::TENANT_PORTAL,
                    notes: filled($validated['notes']) ? $validated['notes'] : null,
                );
            });
        }

        $this->ensureReadingValuePassesBaseValidation($meter, $validated);
        $this->ensureTenantHasNotSubmittedReadingForDate(
            meter: $meter,
            readingDate: $validated['readingDate'],
        );

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

    /**
     * @param  array{meterId: int, readingValue: string|int|float, readingDate: string, notes: string|null}  $validated
     */
    private function ensureReadingValuePassesBaseValidation(Meter $meter, array $validated): void
    {
        $validation = $this->validateReadingValue->handle(
            meter: $meter,
            readingValue: $validated['readingValue'],
            readingDate: $validated['readingDate'],
        );

        if (! $validation->fails()) {
            return;
        }

        throw ValidationException::withMessages($validation->messages);
    }

    private function ensureTenantHasNotSubmittedReadingForDate(Meter $meter, string $readingDate): void
    {
        $hasReadingForDate = MeterReading::query()
            ->forMeter($meter->id)
            ->whereDate('reading_date', $readingDate)
            ->where('submission_method', MeterReadingSubmissionMethod::TENANT_PORTAL)
            ->whereIn('validation_status', [
                MeterReadingValidationStatus::PENDING,
                MeterReadingValidationStatus::VALID,
                MeterReadingValidationStatus::FLAGGED,
            ])
            ->exists();

        if (! $hasReadingForDate) {
            return;
        }

        throw ValidationException::withMessages([
            'readingValue' => __('tenant.pages.readings.validation.duplicate_reading_for_date'),
        ]);
    }

    private function readingRequestInvoice(
        string|int|null $invoiceId,
        int $organizationId,
        int $propertyId,
        int $tenantId,
    ): ?Invoice {
        $resolvedInvoiceId = $this->normalizeInvoiceId($invoiceId);

        if ($resolvedInvoiceId === null) {
            return null;
        }

        return $this->readingRequestInvoiceQuery(
            invoiceId: $resolvedInvoiceId,
            organizationId: $organizationId,
            propertyId: $propertyId,
            tenantId: $tenantId,
        )->first();
    }

    /**
     * @return Builder<Invoice>
     */
    private function readingRequestInvoiceQuery(int $invoiceId, int $organizationId, int $propertyId, int $tenantId): Builder
    {
        return Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'billing_period_start',
                'billing_period_end',
                'due_date',
                'status',
                'approval_status',
                'automation_level',
                'approval_metadata',
            ])
            ->whereKey($invoiceId)
            ->forOrganization($organizationId)
            ->forProperty($propertyId)
            ->forTenant($tenantId)
            ->where('status', InvoiceStatus::DRAFT->value)
            ->where('automation_level', 'reading_request')
            ->whereIn('approval_status', ['waiting_for_readings', 'pending']);
    }

    private function normalizeInvoiceId(string|int|null $invoiceId): ?int
    {
        if ($invoiceId === null) {
            return null;
        }

        $invoiceId = trim((string) $invoiceId);

        if ($invoiceId === '' || ! ctype_digit($invoiceId)) {
            return null;
        }

        $resolvedInvoiceId = (int) $invoiceId;

        return $resolvedInvoiceId > 0 ? $resolvedInvoiceId : null;
    }

    private function ensureMeterIsRequestedForInvoice(Meter $meter, Invoice $invoice): void
    {
        $requiredMeterIds = $this->requiredMeterIds($invoice);

        if ($requiredMeterIds === [] || in_array((int) $meter->id, $requiredMeterIds, true)) {
            return;
        }

        throw new AuthorizationException(__('tenant.pages.readings.unauthorized_meter'));
    }

    /**
     * @return list<int>
     */
    private function requiredMeterIds(Invoice $invoice): array
    {
        $metadata = is_array($invoice->approval_metadata) ? $invoice->approval_metadata : [];

        return collect($metadata['required_inputs'] ?? [])
            ->filter(fn (mixed $input): bool => is_array($input) && ($input['type'] ?? null) === 'meter_reading')
            ->pluck('meter_id')
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    private function ensureTenantHasNotSubmittedReadingForInvoicePeriod(User $tenant, Meter $meter, Invoice $invoice, string $readingDate): void
    {
        $periodStart = $invoice->billing_period_start?->toDateString();
        $periodEnd = $this->readingWindowEnd($invoice);

        if ($periodStart === null || $periodEnd === null) {
            $this->ensureTenantHasNotSubmittedReadingForDate($meter, $readingDate);

            return;
        }

        $hasReadingForInvoicePeriod = MeterReading::query()
            ->forMeter($meter->id)
            ->submittedBy($tenant->id)
            ->betweenDates($periodStart, $periodEnd)
            ->where('submission_method', MeterReadingSubmissionMethod::TENANT_PORTAL)
            ->whereIn('validation_status', [
                MeterReadingValidationStatus::PENDING,
                MeterReadingValidationStatus::VALID,
                MeterReadingValidationStatus::FLAGGED,
            ])
            ->exists();

        if (! $hasReadingForInvoicePeriod) {
            return;
        }

        throw ValidationException::withMessages([
            'readingValue' => __('tenant.pages.readings.validation.duplicate_reading_for_invoice_period'),
        ]);
    }

    private function readingWindowEnd(Invoice $invoice): ?string
    {
        $metadata = is_array($invoice->approval_metadata) ? $invoice->approval_metadata : [];
        $deadline = data_get($metadata, 'reading_submission_deadline');

        if (filled($deadline)) {
            return (string) $deadline;
        }

        return $invoice->due_date?->toDateString() ?? $invoice->billing_period_end?->toDateString();
    }
}
