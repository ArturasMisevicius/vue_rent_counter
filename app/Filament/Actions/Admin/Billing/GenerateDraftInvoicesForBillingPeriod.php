<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Billing;

use App\Enums\BillingMethod;
use App\Enums\InvoiceStatus;
use App\Enums\PropertyAssignmentStatus;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\BillingPeriods\ResolveBillingPeriodForInvoiceCycleAction;
use App\Filament\Support\Admin\Invoices\ReadingRequestInvoiceSnapshotBuilder;
use App\Filament\Support\Admin\ServiceConfigurations\ValidateServiceConfiguration;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\BillingGenerationLog;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Notifications\Billing\BillingGenerationSummaryNotification;
use App\Notifications\Billing\InvoiceReadingRequestNotification;
use App\Services\Billing\InvoiceService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class GenerateDraftInvoicesForBillingPeriod
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
        private readonly ResolveBillingPeriodForInvoiceCycleAction $resolveBillingPeriod,
        private readonly ReadingRequestInvoiceSnapshotBuilder $readingRequestInvoiceSnapshotBuilder,
        private readonly ValidateServiceConfiguration $validateServiceConfiguration,
    ) {}

    /**
     * @param  BillingPeriod|array{
     *     billing_period_start: string,
     *     billing_period_end: string,
     *     reading_submission_deadline: string,
     *     invoice_generation_date?: string|null,
     *     payment_due_date?: string|null,
     *     default_currency?: string|null
     * }  $billingPeriod
     * @return array{
     *     billing_period: BillingPeriod|null,
     *     log: BillingGenerationLog|null,
     *     created: Collection<int, Invoice>,
     *     skipped: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     errors: list<array<string, mixed>>,
     *     notified: int,
     *     summary: array<string, mixed>,
     *     preview: list<array<string, mixed>>
     * }
     */
    public function handle(
        Organization $organization,
        BillingPeriod|array $billingPeriod,
        ?User $actor = null,
        bool $dryRun = false,
        string $source = 'manual',
    ): array {
        $this->subscriptionLimitGuard->ensureCanWrite($organization);

        $periodData = $this->periodData($organization, $billingPeriod);
        $candidates = $this->candidates($organization, $periodData);
        $evaluated = $this->evaluateCandidates($organization, $periodData, $candidates);

        if ($dryRun) {
            return [
                'billing_period' => $billingPeriod instanceof BillingPeriod ? $billingPeriod : null,
                'log' => null,
                'created' => collect(),
                'skipped' => $evaluated['skipped'],
                'warnings' => $evaluated['warnings'],
                'errors' => $evaluated['errors'],
                'notified' => 0,
                'summary' => $this->summary($evaluated, 0),
                'preview' => $evaluated['preview'],
            ];
        }

        $transactionResult = DB::transaction(function () use (
            $organization,
            $billingPeriod,
            $actor,
            $source,
            $periodData,
            $evaluated,
        ): array {
            $resolvedPeriod = $billingPeriod instanceof BillingPeriod
                ? $this->refreshBillingPeriodDates($billingPeriod, $periodData)
                : $this->resolveBillingPeriod->handle(
                    $organization,
                    CarbonImmutable::parse($periodData['billing_period_start']),
                    CarbonImmutable::parse($periodData['billing_period_end']),
                    $periodData['reading_submission_deadline'],
                    invoiceGenerationDate: $periodData['invoice_generation_date'],
                    paymentDueDate: $periodData['payment_due_date'],
                );

            $log = BillingGenerationLog::query()->create([
                'organization_id' => $organization->id,
                'billing_period_id' => $resolvedPeriod->id,
                'initiated_by_user_id' => $actor?->id,
                'source' => $source,
                'status' => 'running',
                'dry_run' => false,
                'billing_period_start' => $periodData['billing_period_start'],
                'billing_period_end' => $periodData['billing_period_end'],
                'invoice_generation_date' => $periodData['invoice_generation_date'],
                'reading_submission_deadline' => $periodData['reading_submission_deadline'],
                'payment_due_date' => $periodData['payment_due_date'],
                'summary' => [],
                'started_at' => now(),
            ]);

            $created = collect();
            $tenantNotifications = collect();
            $skipped = $evaluated['skipped'];
            $warnings = $evaluated['warnings'];
            $errors = $evaluated['errors'];

            foreach ($evaluated['items'] as $item) {
                /** @var PropertyAssignment $assignment */
                $assignment = $item['assignment'];

                $freshDuplicate = $this->activeInvoiceExists(
                    $organization,
                    $assignment,
                    $resolvedPeriod,
                );

                if ($freshDuplicate) {
                    $outcome = $this->outcome($assignment, 'skipped', 'duplicate_active_invoice', __('admin.billing_generation.messages.duplicate_active_invoice'));
                    $outcome['persisted'] = true;
                    $skipped[] = $outcome;
                    $this->createLogItem($log, $resolvedPeriod, $assignment, null, 'skipped', 'duplicate_active_invoice', __('admin.billing_generation.messages.duplicate_active_invoice'));

                    continue;
                }

                $approvalStatus = $item['approval_status'];
                $invoice = $this->invoiceService->createAutomaticBillingPeriodDraft(
                    organization: $organization,
                    assignment: $assignment,
                    billingPeriod: $resolvedPeriod,
                    approvalStatus: $approvalStatus,
                    dueDate: $approvalStatus === 'waiting_for_readings'
                        ? $periodData['reading_submission_deadline']
                        : $periodData['payment_due_date'],
                    currency: $periodData['default_currency'],
                    approvalMetadata: $this->approvalMetadata($assignment, $resolvedPeriod, $periodData, $item, $log),
                    actor: $actor,
                    generatedBy: 'billing:generate-draft-invoices',
                    automationLevel: $approvalStatus === 'waiting_for_readings' ? 'reading_request' : 'automatic_draft',
                );

                $created = $created->push($invoice);
                $this->createLogItem(
                    $log,
                    $resolvedPeriod,
                    $assignment,
                    $invoice,
                    $approvalStatus === 'configuration_error' ? 'error' : 'created',
                    $approvalStatus,
                    $this->messageForApprovalStatus($approvalStatus),
                    ['issues' => $item['issues']],
                );

                if ($approvalStatus === 'configuration_error') {
                    $errors = $this->markOutcomePersisted($errors, $assignment, 'configuration_error');
                }

                if ($approvalStatus === 'waiting_for_readings' && $periodData['send_created_notification']) {
                    $tenantNotifications = $tenantNotifications->push($invoice);
                }
            }

            foreach ([...$skipped, ...$warnings, ...$errors] as $outcome) {
                if (($outcome['persisted'] ?? false) === true) {
                    continue;
                }

                $this->createLogItemFromOutcome($log, $resolvedPeriod, $outcome);
            }

            $summary = $this->summary([
                'items' => $evaluated['items'],
                'skipped' => $skipped,
                'warnings' => $warnings,
                'errors' => $errors,
                'preview' => $evaluated['preview'],
            ], $created->count());

            $log->update([
                'status' => $this->logStatus($summary),
                'eligible_count' => count($evaluated['items']),
                'created_count' => $created->count(),
                'skipped_count' => count($skipped),
                'warning_count' => count($warnings),
                'error_count' => count($errors),
                'summary' => $summary,
                'finished_at' => now(),
            ]);

            return [
                'billing_period' => $resolvedPeriod,
                'log' => $log->fresh(['items']),
                'created' => $created,
                'skipped' => $skipped,
                'warnings' => $warnings,
                'errors' => $errors,
                'tenant_notifications' => $tenantNotifications,
                'summary' => $summary,
                'preview' => $evaluated['preview'],
            ];
        });

        $notified = $this->sendTenantNotifications($transactionResult['tenant_notifications']);

        if ($transactionResult['log'] instanceof BillingGenerationLog) {
            $transactionResult['log']->update(['notified_tenants_count' => $notified]);
            $transactionResult['log'] = $transactionResult['log']->fresh(['items']);
            $this->sendAdminSummary($organization, $transactionResult['log']);
        }

        return [
            'billing_period' => $transactionResult['billing_period'],
            'log' => $transactionResult['log'],
            'created' => $transactionResult['created'],
            'skipped' => $transactionResult['skipped'],
            'warnings' => $transactionResult['warnings'],
            'errors' => $transactionResult['errors'],
            'notified' => $notified,
            'summary' => [
                ...$transactionResult['summary'],
                'notified' => $notified,
            ],
            'preview' => $transactionResult['preview'],
        ];
    }

    /**
     * @return array{
     *     billing_period_start: string,
     *     billing_period_end: string,
     *     reading_submission_deadline: string,
     *     invoice_generation_date: string,
     *     payment_due_date: string,
     *     default_currency: string,
     *     send_created_notification: bool
     * }
     */
    private function periodData(Organization $organization, BillingPeriod|array $billingPeriod): array
    {
        $schedule = $organization->settings?->billingSchedule() ?? [
            'default_currency' => 'EUR',
            'send_created_notification' => true,
        ];

        if ($billingPeriod instanceof BillingPeriod) {
            return [
                'billing_period_start' => $billingPeriod->starts_at?->toDateString() ?? now()->startOfMonth()->toDateString(),
                'billing_period_end' => $billingPeriod->ends_at?->toDateString() ?? now()->endOfMonth()->toDateString(),
                'reading_submission_deadline' => $billingPeriod->reading_submission_deadline?->toDateString()
                    ?? now()->addDays(5)->toDateString(),
                'invoice_generation_date' => $billingPeriod->invoice_generation_date?->toDateString()
                    ?? now()->toDateString(),
                'payment_due_date' => $billingPeriod->payment_due_date?->toDateString()
                    ?? now()->addDays(19)->toDateString(),
                'default_currency' => (string) ($schedule['default_currency'] ?? 'EUR'),
                'send_created_notification' => (bool) ($schedule['send_created_notification'] ?? true),
            ];
        }

        $periodEnd = CarbonImmutable::parse((string) $billingPeriod['billing_period_end'])->startOfDay();
        $readingDeadline = filled($billingPeriod['reading_submission_deadline'] ?? null)
            ? CarbonImmutable::parse((string) $billingPeriod['reading_submission_deadline'])->startOfDay()
            : $periodEnd->addDays(5);

        return [
            'billing_period_start' => CarbonImmutable::parse((string) $billingPeriod['billing_period_start'])->toDateString(),
            'billing_period_end' => $periodEnd->toDateString(),
            'reading_submission_deadline' => $readingDeadline->toDateString(),
            'invoice_generation_date' => filled($billingPeriod['invoice_generation_date'] ?? null)
                ? CarbonImmutable::parse((string) $billingPeriod['invoice_generation_date'])->toDateString()
                : now()->toDateString(),
            'payment_due_date' => filled($billingPeriod['payment_due_date'] ?? null)
                ? CarbonImmutable::parse((string) $billingPeriod['payment_due_date'])->toDateString()
                : $readingDeadline->addDays(14)->toDateString(),
            'default_currency' => strtoupper((string) ($billingPeriod['default_currency'] ?? 'EUR')),
            'send_created_notification' => (bool) ($billingPeriod['send_created_notification'] ?? true),
        ];
    }

    /**
     * @return Collection<int, PropertyAssignment>
     */
    private function candidates(Organization $organization, array $periodData): Collection
    {
        $periodStart = CarbonImmutable::parse($periodData['billing_period_start'])->startOfDay();
        $periodEnd = CarbonImmutable::parse($periodData['billing_period_end'])->endOfDay();

        return PropertyAssignment::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'unit_area_sqm',
                'status',
                'assigned_at',
                'unassigned_at',
                'billing_start_date',
                'billing_end_date',
            ])
            ->forOrganization($organization->id)
            ->where('assigned_at', '<=', $periodEnd)
            ->where(function (Builder $query) use ($periodStart): void {
                $query
                    ->whereNull('unassigned_at')
                    ->orWhere('unassigned_at', '>=', $periodStart);
            })
            ->with([
                'tenant:id,organization_id,name,email,role,status,tenant_status,locale',
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.building:id,organization_id,name',
                'property.meters' => fn ($meterQuery) => $meterQuery
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                    ->active()
                    ->ordered(),
                'property.serviceConfigurations' => fn ($configurationQuery) => $configurationQuery
                    ->activeOn($periodEnd)
                    ->with([
                        'utilityService:id,organization_id,name,unit_of_measurement,service_type_bridge,description',
                        'provider:id,organization_id,name,service_type',
                        'tariff:id,provider_id,name,configuration',
                    ])
                    ->ordered(),
            ])
            ->latestAssignedFirst()
            ->get()
            ->unique(fn (PropertyAssignment $assignment): string => $this->invoiceKey($assignment))
            ->values();
    }

    /**
     * @param  Collection<int, PropertyAssignment>  $assignments
     * @return array{
     *     items: list<array<string, mixed>>,
     *     skipped: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     errors: list<array<string, mixed>>,
     *     preview: list<array<string, mixed>>
     * }
     */
    private function evaluateCandidates(Organization $organization, array $periodData, Collection $assignments): array
    {
        $items = [];
        $skipped = [];
        $warnings = [];
        $errors = [];
        $preview = [];
        $periodStart = CarbonImmutable::parse($periodData['billing_period_start'])->startOfDay();
        $periodEnd = CarbonImmutable::parse($periodData['billing_period_end'])->endOfDay();
        $existingInvoiceKeys = $this->existingInvoiceKeys($organization, $assignments, $periodStart, $periodEnd);

        foreach ($assignments as $assignment) {
            if (! $this->tenantIsActive($assignment->tenant)) {
                $outcome = $this->outcome($assignment, 'skipped', 'inactive_tenant', __('admin.billing_generation.messages.inactive_tenant'));
                $skipped[] = $outcome;
                $preview[] = $outcome;

                continue;
            }

            if (! $this->assignmentIsActiveForPeriod($assignment, $periodStart, $periodEnd)) {
                $outcome = $this->outcome($assignment, 'skipped', 'inactive_assignment', __('admin.billing_generation.messages.inactive_assignment'));
                $skipped[] = $outcome;
                $preview[] = $outcome;

                continue;
            }

            if (isset($existingInvoiceKeys[$this->invoiceKey($assignment)])) {
                $outcome = $this->outcome($assignment, 'skipped', 'duplicate_active_invoice', __('admin.billing_generation.messages.duplicate_active_invoice'));
                $skipped[] = $outcome;
                $preview[] = $outcome;

                continue;
            }

            $services = $this->billableServices($assignment);

            if ($services->isEmpty()) {
                $outcome = $this->outcome($assignment, 'warning', 'no_billable_services', __('admin.billing_generation.messages.no_billable_services'));
                $warnings[] = $outcome;
                $skipped[] = $outcome;
                $preview[] = $outcome;

                continue;
            }

            $configurationIssues = $this->configurationIssues($services);
            $meters = $this->activeMeters($assignment);
            $approvalStatus = $configurationIssues === []
                ? ($meters->isNotEmpty() ? 'waiting_for_readings' : 'ready_for_review')
                : 'configuration_error';
            $item = [
                'assignment' => $assignment,
                'approval_status' => $approvalStatus,
                'issues' => $configurationIssues,
                'services_count' => $services->count(),
                'meters_count' => $meters->count(),
            ];
            $outcome = $this->outcome(
                $assignment,
                $approvalStatus === 'configuration_error' ? 'error' : 'created',
                $approvalStatus,
                $this->messageForApprovalStatus($approvalStatus),
                [
                    'issues' => $configurationIssues,
                    'services_count' => $services->count(),
                    'meters_count' => $meters->count(),
                ],
            );

            $items[] = $item;
            $preview[] = $outcome;

            if ($approvalStatus === 'configuration_error') {
                $errors[] = $outcome;
            }
        }

        return [
            'items' => $items,
            'skipped' => $skipped,
            'warnings' => $warnings,
            'errors' => $errors,
            'preview' => $preview,
        ];
    }

    private function refreshBillingPeriodDates(BillingPeriod $billingPeriod, array $periodData): BillingPeriod
    {
        $billingPeriod->fill([
            'reading_submission_deadline' => $periodData['reading_submission_deadline'],
            'invoice_generation_date' => $periodData['invoice_generation_date'],
            'payment_due_date' => $periodData['payment_due_date'],
        ]);
        $billingPeriod->save();

        return $billingPeriod->fresh() ?? $billingPeriod;
    }

    private function tenantIsActive(?User $tenant): bool
    {
        $tenantStatus = $tenant?->tenant_status instanceof \BackedEnum
            ? $tenant->tenant_status->value
            : (string) $tenant?->tenant_status;

        return $tenant instanceof User
            && $tenant->isTenant()
            && $tenant->status === UserStatus::ACTIVE
            && $tenantStatus === 'active';
    }

    private function assignmentIsActiveForPeriod(
        PropertyAssignment $assignment,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
    ): bool {
        $status = $assignment->status instanceof PropertyAssignmentStatus
            ? $assignment->status
            : PropertyAssignmentStatus::tryFrom((string) $assignment->status);

        if (! in_array($status, [PropertyAssignmentStatus::ACTIVE, PropertyAssignmentStatus::MOVE_OUT_SCHEDULED], true)) {
            return false;
        }

        if ($assignment->assigned_at === null || $assignment->assigned_at->greaterThan($periodEnd)) {
            return false;
        }

        if ($assignment->unassigned_at !== null && $assignment->unassigned_at->lessThan($periodStart)) {
            return false;
        }

        if ($assignment->billing_end_date !== null && $assignment->billing_end_date->lessThan($periodStart)) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<int, ServiceConfiguration>
     */
    private function billableServices(PropertyAssignment $assignment): Collection
    {
        $property = $assignment->property;

        if (! $property instanceof Property) {
            return collect();
        }

        return collect($property->serviceConfigurations)
            ->filter(fn (ServiceConfiguration $configuration): bool => $configuration->billing_method instanceof BillingMethod
                && $configuration->billing_method->createsAutomaticInvoiceItems())
            ->values();
    }

    /**
     * @return Collection<int, Meter>
     */
    private function activeMeters(PropertyAssignment $assignment): Collection
    {
        return $assignment->property instanceof Property
            ? collect($assignment->property->meters)->values()
            : collect();
    }

    /**
     * @param  Collection<int, ServiceConfiguration>  $services
     * @return list<string>
     */
    private function configurationIssues(Collection $services): array
    {
        return $services
            ->flatMap(function (ServiceConfiguration $configuration): array {
                $validation = $this->validateServiceConfiguration->handle($configuration);

                return array_map(
                    fn (string $message): string => (string) ($configuration->service_name ?: $configuration->utilityService?->name ?: $configuration->id).': '.$message,
                    $validation['blocking_errors'],
                );
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, PropertyAssignment>  $assignments
     * @return array<string, true>
     */
    private function existingInvoiceKeys(
        Organization $organization,
        Collection $assignments,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $propertyIds = $assignments->pluck('property_id')->filter()->unique()->values()->all();
        $tenantIds = $assignments->pluck('tenant_user_id')->filter()->unique()->values()->all();

        if ($propertyIds === [] || $tenantIds === []) {
            return [];
        }

        return Invoice::query()
            ->select(['property_id', 'tenant_user_id'])
            ->forOrganization($organization->id)
            ->forBillingPeriod($periodStart, $periodEnd)
            ->whereIn('property_id', $propertyIds)
            ->whereIn('tenant_user_id', $tenantIds)
            ->where('status', '!=', InvoiceStatus::VOID->value)
            ->get()
            ->mapWithKeys(fn (Invoice $invoice): array => [
                $this->invoiceKeyFromValues((int) $invoice->property_id, (int) $invoice->tenant_user_id) => true,
            ])
            ->all();
    }

    private function activeInvoiceExists(
        Organization $organization,
        PropertyAssignment $assignment,
        BillingPeriod $billingPeriod,
    ): bool {
        return Invoice::query()
            ->select(['id'])
            ->forOrganization($organization->id)
            ->forProperty((int) $assignment->property_id)
            ->forTenant((int) $assignment->tenant_user_id)
            ->where(function (Builder $query) use ($billingPeriod): void {
                $query
                    ->where('billing_period_id', $billingPeriod->id)
                    ->orWhere(function (Builder $dateQuery) use ($billingPeriod): void {
                        $dateQuery
                            ->whereDate('billing_period_start', $billingPeriod->starts_at?->toDateString())
                            ->whereDate('billing_period_end', $billingPeriod->ends_at?->toDateString());
                    });
            })
            ->where('status', '!=', InvoiceStatus::VOID->value)
            ->exists();
    }

    private function invoiceKey(PropertyAssignment $assignment): string
    {
        return $this->invoiceKeyFromValues((int) $assignment->property_id, (int) $assignment->tenant_user_id);
    }

    private function invoiceKeyFromValues(int $propertyId, int $tenantId): string
    {
        return $propertyId.':'.$tenantId;
    }

    /**
     * @return array<string, mixed>
     */
    private function approvalMetadata(
        PropertyAssignment $assignment,
        BillingPeriod $billingPeriod,
        array $periodData,
        array $item,
        BillingGenerationLog $log,
    ): array {
        $periodStart = CarbonImmutable::parse($periodData['billing_period_start'])->startOfDay();
        $periodEnd = CarbonImmutable::parse($periodData['billing_period_end'])->endOfDay();
        $snapshot = $this->readingRequestInvoiceSnapshotBuilder->handle(
            assignment: $assignment,
            billingPeriod: $billingPeriod,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            deadline: $periodData['reading_submission_deadline'],
        );

        return [
            ...$snapshot,
            'workflow' => 'automatic_monthly_draft_invoice',
            'source' => 'billing:generate-draft-invoices',
            'request_status' => $item['approval_status'],
            'billing_generation_log_id' => $log->id,
            'billing_period_id' => $billingPeriod->id,
            'reading_submission_deadline' => $periodData['reading_submission_deadline'],
            'invoice_generation_date' => $periodData['invoice_generation_date'],
            'payment_due_date' => $periodData['payment_due_date'],
            'configuration_issues' => $item['issues'],
        ];
    }

    private function messageForApprovalStatus(string $approvalStatus): string
    {
        return match ($approvalStatus) {
            'waiting_for_readings' => __('admin.billing_generation.messages.waiting_for_readings'),
            'ready_for_review' => __('admin.billing_generation.messages.ready_for_review'),
            'configuration_error' => __('admin.billing_generation.messages.configuration_error'),
            default => $approvalStatus,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function outcome(
        PropertyAssignment $assignment,
        string $level,
        string $code,
        string $message,
        array $context = [],
    ): array {
        return [
            'assignment_key' => $this->invoiceKey($assignment),
            'assignment_id' => $assignment->id,
            'tenant_id' => $assignment->tenant_user_id,
            'property_id' => $assignment->property_id,
            'tenant_name' => (string) ($assignment->tenant?->name ?? ''),
            'property_name' => (string) ($assignment->property?->displayName() ?? ''),
            'level' => $level,
            'code' => $code,
            'message' => $message,
            'context' => $context,
        ];
    }

    private function createLogItemFromOutcome(
        BillingGenerationLog $log,
        BillingPeriod $billingPeriod,
        array $outcome,
    ): void {
        $this->createLogItem(
            $log,
            $billingPeriod,
            null,
            null,
            (string) $outcome['level'],
            (string) $outcome['code'],
            (string) $outcome['message'],
            $outcome['context'] ?? [],
            is_numeric($outcome['assignment_id'] ?? null) ? (int) $outcome['assignment_id'] : null,
            is_numeric($outcome['tenant_id'] ?? null) ? (int) $outcome['tenant_id'] : null,
            is_numeric($outcome['property_id'] ?? null) ? (int) $outcome['property_id'] : null,
        );
    }

    private function createLogItem(
        BillingGenerationLog $log,
        BillingPeriod $billingPeriod,
        ?PropertyAssignment $assignment,
        ?Invoice $invoice,
        string $level,
        string $code,
        string $message,
        array $context = [],
        ?int $assignmentId = null,
        ?int $tenantId = null,
        ?int $propertyId = null,
    ): void {
        $log->items()->create([
            'organization_id' => $log->organization_id,
            'billing_period_id' => $billingPeriod->id,
            'invoice_id' => $invoice?->id,
            'property_assignment_id' => $assignment?->id ?? $assignmentId,
            'tenant_user_id' => $assignment?->tenant_user_id ?? $tenantId,
            'property_id' => $assignment?->property_id ?? $propertyId,
            'level' => $level,
            'code' => $code,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(array $evaluated, int $createdCount): array
    {
        return [
            'eligible' => count($evaluated['items']),
            'created' => $createdCount,
            'skipped' => count($evaluated['skipped']),
            'warnings' => count($evaluated['warnings']),
            'errors' => count($evaluated['errors']),
            'notified' => 0,
            'preview' => $evaluated['preview'],
        ];
    }

    private function logStatus(array $summary): string
    {
        if (($summary['errors'] ?? 0) > 0) {
            return 'completed_with_errors';
        }

        if (($summary['warnings'] ?? 0) > 0 || ($summary['skipped'] ?? 0) > 0) {
            return 'completed_with_warnings';
        }

        return 'completed';
    }

    /**
     * @param  list<array<string, mixed>>  $outcomes
     * @return list<array<string, mixed>>
     */
    private function markOutcomePersisted(array $outcomes, PropertyAssignment $assignment, string $code): array
    {
        return array_map(function (array $outcome) use ($assignment, $code): array {
            if (
                (int) ($outcome['assignment_id'] ?? 0) === (int) $assignment->id
                && ($outcome['code'] ?? null) === $code
            ) {
                $outcome['persisted'] = true;
            }

            return $outcome;
        }, $outcomes);
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     */
    private function sendTenantNotifications(Collection $invoices): int
    {
        $notified = 0;

        foreach ($invoices as $invoice) {
            $invoice->loadMissing('tenant', 'billingPeriod');

            if (! $invoice->tenant instanceof User) {
                continue;
            }

            $invoice->tenant->notify(new InvoiceReadingRequestNotification($invoice));
            $notified++;
        }

        return $notified;
    }

    private function sendAdminSummary(Organization $organization, BillingGenerationLog $log): void
    {
        $recipients = User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
            ->forOrganization($organization->id)
            ->active()
            ->whereIn('role', ['admin', 'manager'])
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new BillingGenerationSummaryNotification($log));
    }
}
