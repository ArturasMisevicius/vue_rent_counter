<?php

declare(strict_types=1);

namespace App\Filament\Actions\Tenant\Readings;

use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\TenantStatus;
use App\Filament\Actions\Admin\MeterReadings\CreateMeterReadingAction;
use App\Filament\Support\Admin\ReadingValidation\ReadingValidationResult;
use App\Filament\Support\Admin\ReadingValidation\ValidateReadingValue;
use App\Filament\Support\TenantKyc\TenantKycGate;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Http\Requests\Tenant\StoreMeterReadingRequest;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\Billing\UniversalBillingCalculator;
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
        protected TenantKycGate $tenantKycGate,
        protected UniversalBillingCalculator $calculator,
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

        if ($this->tenantKycGate->blocksReadingSubmission($tenant)) {
            throw new AuthorizationException(__('tenant.pages.verification.reading_submission_blocked'));
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

        if (! $invoice instanceof Invoice) {
            throw ValidationException::withMessages([
                'readingValue' => $this->readingRequestUnavailableMessage($invoiceId),
            ]);
        }

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

            $this->ensureInvoiceHasBillingPeriod($lockedInvoice);
            $this->ensureInvoiceReadingWindowIsOpen($lockedInvoice);
            $this->ensureMeterIsRequestedForInvoice($meter, $lockedInvoice);

            $existingReading = $this->existingTenantReadingForInvoicePeriod(
                tenant: $tenant,
                meter: $meter,
                invoice: $lockedInvoice,
                readingDate: (string) $validated['readingDate'],
            );
            $this->ensureExistingReadingCanBeEdited($existingReading);

            $validation = $this->ensureReadingValuePassesBaseValidation($meter, $validated, $existingReading?->id);

            if ($existingReading instanceof MeterReading) {
                return $this->updateExistingReading(
                    reading: $existingReading,
                    tenant: $tenant,
                    invoice: $lockedInvoice,
                    readingValue: $validated['readingValue'],
                    readingDate: $validated['readingDate'],
                    notes: filled($validated['notes']) ? $validated['notes'] : null,
                    validation: $validation,
                );
            }

            $reading = $this->createMeterReadingAction->handle(
                meter: $meter,
                readingValue: $validated['readingValue'],
                readingDate: $validated['readingDate'],
                submittedBy: $tenant,
                submissionMethod: MeterReadingSubmissionMethod::TENANT_PORTAL,
                notes: filled($validated['notes']) ? $validated['notes'] : null,
            );

            return $this->scopeCreatedReadingToInvoice(
                reading: $reading,
                tenant: $tenant,
                invoice: $lockedInvoice,
                notes: filled($validated['notes']) ? $validated['notes'] : null,
                validation: $validation,
            );
        });
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
    private function ensureReadingValuePassesBaseValidation(Meter $meter, array $validated, ?int $ignoreReadingId = null): ReadingValidationResult
    {
        $validation = $this->validateReadingValue->handle(
            meter: $meter,
            readingValue: $validated['readingValue'],
            readingDate: $validated['readingDate'],
            ignoreReadingId: $ignoreReadingId,
        );

        if (! $validation->fails()) {
            return $validation;
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
                'billing_period_id',
                'property_assignment_id',
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
            ->whereIn('approval_status', ['waiting_for_readings', 'pending', 'readings_submitted', 'readings_rejected']);
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

    private function readingRequestUnavailableMessage(string|int|null $invoiceId): string
    {
        if (blank($invoiceId)) {
            return __('tenant.pages.readings.no_open_request');
        }

        return __('tenant.pages.readings.invoice_request_unavailable');
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

    private function ensureInvoiceHasBillingPeriod(Invoice $invoice): void
    {
        if ($invoice->billing_period_id !== null) {
            return;
        }

        throw ValidationException::withMessages([
            'readingValue' => __('tenant.pages.readings.invoice_request_unavailable'),
        ]);
    }

    private function ensureInvoiceReadingWindowIsOpen(Invoice $invoice): void
    {
        $deadline = $this->readingWindowEnd($invoice);

        if ($deadline === null || now()->toDateString() <= $deadline) {
            return;
        }

        throw ValidationException::withMessages([
            'readingValue' => __('tenant.pages.readings.invoice_request_unavailable'),
        ]);
    }

    private function existingTenantReadingForInvoicePeriod(User $tenant, Meter $meter, Invoice $invoice, string $readingDate): ?MeterReading
    {
        $periodStart = $invoice->billing_period_start?->toDateString();
        $periodEnd = $this->readingWindowEnd($invoice);

        if ($periodStart === null || $periodEnd === null) {
            $this->ensureTenantHasNotSubmittedReadingForDate($meter, $readingDate);

            return null;
        }

        return MeterReading::query()
            ->select([
                'id',
                'organization_id',
                'billing_period_id',
                'property_id',
                'tenant_id',
                'meter_id',
                'submitted_by_user_id',
                'reading_value',
                'reading_date',
                'previous_value',
                'current_value',
                'consumption',
                'validation_status',
                'status',
                'submitted_at',
                'approved_by_user_id',
                'approved_at',
                'rejected_by_user_id',
                'rejected_at',
                'rejection_reason',
                'corrected_by_user_id',
                'correction_reason',
                'tenant_comment',
                'voided_at',
                'submission_method',
                'reading_type',
                'property_assignment_id',
                'invoice_id',
                'notes',
                'created_at',
                'updated_at',
            ])
            ->with(['invoice:id,due_date,billing_period_end,approval_metadata'])
            ->forOrganization((int) $invoice->organization_id)
            ->forProperty((int) $invoice->property_id)
            ->forMeter((int) $meter->id)
            ->where(function (Builder $query) use ($tenant): void {
                $query
                    ->where('tenant_id', $tenant->id)
                    ->orWhere('submitted_by_user_id', $tenant->id);
            })
            ->where(function (Builder $query) use ($invoice, $periodStart, $periodEnd): void {
                $query
                    ->where('billing_period_id', $invoice->billing_period_id)
                    ->orWhere('invoice_id', $invoice->id)
                    ->orWhere(function (Builder $dateQuery) use ($periodStart, $periodEnd): void {
                        $dateQuery
                            ->whereNull('billing_period_id')
                            ->whereDate('reading_date', '>=', $periodStart)
                            ->whereDate('reading_date', '<=', $periodEnd);
                    });
            })
            ->whereIn('status', [
                ...MeterReadingStatus::activeValues(),
                MeterReadingStatus::REJECTED->value,
            ])
            ->latestFirst()
            ->lockForUpdate()
            ->first();
    }

    private function ensureExistingReadingCanBeEdited(?MeterReading $reading): void
    {
        if (! $reading instanceof MeterReading) {
            return;
        }

        if ($reading->isTenantEditable()) {
            return;
        }

        throw ValidationException::withMessages([
            'readingValue' => __('tenant.pages.readings.validation.duplicate_reading_for_invoice_period'),
        ]);
    }

    private function scopeCreatedReadingToInvoice(
        MeterReading $reading,
        User $tenant,
        Invoice $invoice,
        ?string $notes,
        ReadingValidationResult $validation,
    ): MeterReading {
        $reading->forceFill($this->scopedReadingAttributes(
            tenant: $tenant,
            invoice: $invoice,
            readingValue: $reading->reading_value,
            notes: $notes,
            validation: $validation,
        ))->save();

        $freshReading = $reading->fresh(['meter', 'invoice']);
        $freshReading->recordVersion('submitted', $tenant);

        return $freshReading;
    }

    private function updateExistingReading(
        MeterReading $reading,
        User $tenant,
        Invoice $invoice,
        string|int|float $readingValue,
        string $readingDate,
        ?string $notes,
        ReadingValidationResult $validation,
    ): MeterReading {
        $reading->forceFill([
            'reading_value' => $readingValue,
            'reading_date' => $readingDate,
            ...$this->scopedReadingAttributes(
                tenant: $tenant,
                invoice: $invoice,
                readingValue: $readingValue,
                notes: $notes,
                validation: $validation,
            ),
        ])->save();

        $freshReading = $reading->fresh(['meter', 'invoice']);
        $freshReading->recordVersion('tenant_updated', $tenant);

        return $freshReading;
    }

    /**
     * @return array<string, mixed>
     */
    private function scopedReadingAttributes(
        User $tenant,
        Invoice $invoice,
        string|int|float $readingValue,
        ?string $notes,
        ReadingValidationResult $validation,
    ): array {
        $previousValue = $validation->previousReading instanceof MeterReading
            ? (string) ($validation->previousReading->current_value ?? $validation->previousReading->reading_value)
            : null;

        return [
            'billing_period_id' => $invoice->billing_period_id,
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'property_assignment_id' => $invoice->property_assignment_id,
            'previous_value' => $previousValue,
            'current_value' => $readingValue,
            'consumption' => $previousValue === null
                ? null
                : $this->calculator->quantity($this->calculator->subtract($readingValue, $previousValue, 3)),
            'validation_status' => $validation->status,
            'status' => MeterReadingStatus::SUBMITTED,
            'submitted_at' => now(),
            'approved_by_user_id' => null,
            'approved_at' => null,
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'corrected_by_user_id' => null,
            'correction_reason' => null,
            'tenant_comment' => $notes,
            'voided_at' => null,
        ];
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
