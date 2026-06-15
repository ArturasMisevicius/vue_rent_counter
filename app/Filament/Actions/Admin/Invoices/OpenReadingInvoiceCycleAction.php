<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Invoices;

use App\Filament\Actions\Admin\BillingPeriods\ResolveBillingPeriodForInvoiceCycleAction;
use App\Filament\Support\Admin\Invoices\ReadingRequestInvoiceSnapshotBuilder;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Http\Requests\Admin\Invoices\OpenReadingInvoiceCycleRequest;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Models\User;
use App\Notifications\Billing\InvoiceReadingRequestNotification;
use App\Services\Billing\InvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OpenReadingInvoiceCycleAction
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
        private readonly ResolveBillingPeriodForInvoiceCycleAction $resolveBillingPeriodForInvoiceCycle,
        private readonly ReadingRequestInvoiceSnapshotBuilder $readingRequestInvoiceSnapshotBuilder,
    ) {}

    /**
     * @param  array{billing_period_start: string, billing_period_end: string, due_date: string, invoice_generation_date?: string|null, payment_due_date?: string|null}  $attributes
     * @return array{
     *     billing_period: BillingPeriod,
     *     created: Collection<int, Invoice>,
     *     skipped: array<int, array{
     *         assignment_id: int,
     *         tenant_id: int,
     *         property_id: int,
     *         tenant_name: string,
     *         property_name: string,
     *         reason: string
     *     }>,
     *     notified: int
     * }
     */
    public function handle(Organization $organization, array $attributes, ?User $actor = null): array
    {
        $this->subscriptionLimitGuard->ensureCanWrite($organization);

        /** @var OpenReadingInvoiceCycleRequest $request */
        $request = new OpenReadingInvoiceCycleRequest;
        $validated = $request->validatePayload($attributes, $actor ?? auth()->user());
        $periodStart = CarbonImmutable::parse((string) $validated['billing_period_start'])->startOfDay();
        $periodEnd = CarbonImmutable::parse((string) $validated['billing_period_end'])->endOfDay();
        $dueDate = CarbonImmutable::parse((string) $validated['due_date'])->toDateString();
        $invoiceGenerationDate = filled($validated['invoice_generation_date'] ?? null)
            ? CarbonImmutable::parse((string) $validated['invoice_generation_date'])->toDateString()
            : null;
        $paymentDueDate = filled($validated['payment_due_date'] ?? null)
            ? CarbonImmutable::parse((string) $validated['payment_due_date'])->toDateString()
            : null;
        $billingPeriod = $this->resolveBillingPeriodForInvoiceCycle->handle(
            $organization,
            $periodStart,
            $periodEnd,
            $dueDate,
            invoiceGenerationDate: $invoiceGenerationDate,
            paymentDueDate: $paymentDueDate,
        );
        $created = collect();
        $skipped = [];
        $notified = 0;

        PropertyAssignment::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'unit_area_sqm',
                'assigned_at',
                'unassigned_at',
            ])
            ->forOrganization($organization->id)
            ->activeDuring($periodStart, $periodEnd)
            ->whereHas(
                'tenant',
                fn (Builder $tenantQuery): Builder => $tenantQuery->tenants()->active(),
            )
            ->whereHas(
                'property.meters',
                fn (Builder $meterQuery): Builder => $meterQuery->active(),
            )
            ->with([
                'tenant:id,organization_id,name,email,role,status,locale',
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.building:id,organization_id,name',
                'property.meters' => fn ($meterQuery) => $meterQuery
                    ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
                    ->active()
                    ->ordered(),
                'property.serviceConfigurations' => fn ($configurationQuery) => $configurationQuery
                    ->activeOn($periodEnd)
                    ->with(['utilityService:id,organization_id,name,unit_of_measurement,service_type_bridge,description'])
                    ->ordered(),
            ])
            ->chunkById(100, function (Collection $assignments) use (
                $organization,
                $periodStart,
                $periodEnd,
                $dueDate,
                $billingPeriod,
                $actor,
                $created,
                &$skipped,
                &$notified,
            ): void {
                $existingInvoiceKeys = $this->existingInvoiceKeys($organization, $assignments, $periodStart, $periodEnd);

                foreach ($assignments as $assignment) {
                    $assignmentKey = $this->assignmentKey($assignment);

                    if (isset($existingInvoiceKeys[$assignmentKey])) {
                        $skipped[] = $this->skippedAssignment($assignment, 'already_open');

                        continue;
                    }

                    if (! $assignment->tenant instanceof User) {
                        $skipped[] = $this->skippedAssignment($assignment, 'tenant_unavailable');

                        continue;
                    }

                    $readingRequestSnapshot = $this->readingRequestInvoiceSnapshotBuilder->handle(
                        assignment: $assignment,
                        billingPeriod: $billingPeriod,
                        periodStart: $periodStart,
                        periodEnd: $periodEnd,
                        deadline: $dueDate,
                    );

                    $invoice = $this->invoiceService->createReadingRequestDraft(
                        $organization,
                        $assignment,
                        $periodStart,
                        $periodEnd,
                        $dueDate,
                        $actor,
                        $billingPeriod,
                        $readingRequestSnapshot,
                    );

                    $assignment->tenant->notify(new InvoiceReadingRequestNotification($invoice));

                    $created->push($invoice);
                    $notified++;
                }
            });

        return [
            'billing_period' => $billingPeriod,
            'created' => $created,
            'skipped' => $skipped,
            'notified' => $notified,
        ];
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
        $propertyIds = $assignments
            ->pluck('property_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $tenantIds = $assignments
            ->pluck('tenant_user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($propertyIds === [] || $tenantIds === []) {
            return [];
        }

        return Invoice::query()
            ->select(['property_id', 'tenant_user_id'])
            ->forOrganization($organization->id)
            ->forBillingPeriod($periodStart, $periodEnd)
            ->whereIn('property_id', $propertyIds)
            ->whereIn('tenant_user_id', $tenantIds)
            ->get()
            ->mapWithKeys(fn (Invoice $invoice): array => [
                $this->invoiceKey((int) $invoice->property_id, (int) $invoice->tenant_user_id) => true,
            ])
            ->all();
    }

    private function assignmentKey(PropertyAssignment $assignment): string
    {
        return $this->invoiceKey((int) $assignment->property_id, (int) $assignment->tenant_user_id);
    }

    private function invoiceKey(int $propertyId, int $tenantId): string
    {
        return $propertyId.':'.$tenantId;
    }

    /**
     * @return array{
     *     assignment_id: int,
     *     tenant_id: int,
     *     property_id: int,
     *     tenant_name: string,
     *     property_name: string,
     *     reason: string
     * }
     */
    private function skippedAssignment(PropertyAssignment $assignment, string $reason): array
    {
        return [
            'assignment_id' => $assignment->id,
            'tenant_id' => $assignment->tenant_user_id,
            'property_id' => $assignment->property_id,
            'tenant_name' => (string) ($assignment->tenant?->name ?? ''),
            'property_name' => (string) ($assignment->property?->displayName() ?? ''),
            'reason' => $reason,
        ];
    }
}
