<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Admin\Invoices\InvoiceEligibilityWindow;
use App\Filament\Support\Admin\Invoices\InvoiceLineItemCalculator;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class GenerateBulkInvoicesAction
{
    public function __construct(
        protected GenerateInvoiceLineItemsAction $generateInvoiceLineItemsAction,
        protected InvoiceEligibilityWindow $invoiceEligibilityWindow,
        protected InvoiceLineItemCalculator $invoiceLineItemCalculator,
    ) {}

    /**
     * @return Collection<int, Invoice>|array{created: Collection<int, Invoice>, skipped: array<int, array{tenant_id: int, property_id: int, reason: string}>}
     */
    public function handle(
        Organization $organization,
        array|CarbonInterface|string $billingPeriodStart,
        CarbonInterface|string|User|null $billingPeriodEnd = null,
        ?User $actor = null,
    ): Collection|array {
        if (is_array($billingPeriodStart)) {
            $resolvedActor = $billingPeriodEnd instanceof User ? $billingPeriodEnd : $actor;

            return $this->handleAttributes($organization, $billingPeriodStart, $resolvedActor);
        }

        if (! $billingPeriodEnd instanceof CarbonInterface && ! is_string($billingPeriodEnd)) {
            throw new InvalidArgumentException('The billing period end date must be a string or Carbon instance.');
        }

        return $this->generateInvoices(
            $organization,
            $billingPeriodStart,
            $billingPeriodEnd,
        )['created'];
    }

    /**
     * @param  array{billing_period_start: string, billing_period_end: string, due_date?: string}  $attributes
     * @return array{created: Collection<int, Invoice>, skipped: array<int, array{tenant_id: int, property_id: int, reason: string}>}
     */
    protected function handleAttributes(Organization $organization, array $attributes, ?User $actor = null): array
    {
        return $this->generateInvoices(
            $organization,
            $attributes['billing_period_start'],
            $attributes['billing_period_end'],
            $attributes['due_date'] ?? null,
            $actor,
        );
    }

    /**
     * @return array{created: Collection<int, Invoice>, skipped: array<int, array{tenant_id: int, property_id: int, reason: string}>}
     */
    protected function generateInvoices(
        Organization $organization,
        CarbonInterface|string $billingPeriodStart,
        CarbonInterface|string $billingPeriodEnd,
        ?string $dueDate = null,
        ?User $actor = null,
    ): array {
        $periodStart = $this->normalizeDate($billingPeriodStart)->startOfDay();
        $periodEnd = $this->normalizeDate($billingPeriodEnd)->endOfDay();
        $resolvedDueDate = $dueDate !== null
            ? $this->normalizeDate($dueDate)->toDateString()
            : $periodEnd->copy()->addDays(14)->toDateString();

        $created = collect();
        $skipped = [];

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
            ->with([
                'property:id,organization_id,building_id,name,unit_number',
                'tenant:id,organization_id,name,email',
            ])
            ->get()
            ->each(function (PropertyAssignment $assignment) use (
                $organization,
                $periodStart,
                $periodEnd,
                $resolvedDueDate,
                $actor,
                &$created,
                &$skipped,
            ): void {
                if (! $this->invoiceEligibilityWindow->allows($assignment, $periodStart, $periodEnd)) {
                    return;
                }

                $alreadyGenerated = Invoice::query()
                    ->select(['id'])
                    ->forOrganization($organization->id)
                    ->forProperty($assignment->property_id)
                    ->forTenant($assignment->tenant_user_id)
                    ->forBillingPeriod($periodStart, $periodEnd)
                    ->exists();

                if ($alreadyGenerated) {
                    $skipped[] = [
                        'tenant_id' => $assignment->tenant_user_id,
                        'property_id' => $assignment->property_id,
                        'reason' => 'already_billed',
                    ];

                    return;
                }

                $lineItems = $this->lineItemsFor($assignment, $periodStart, $periodEnd);

                $invoice = Invoice::query()->create([
                    'organization_id' => $organization->id,
                    'property_id' => $assignment->property_id,
                    'tenant_user_id' => $assignment->tenant_user_id,
                    'invoice_number' => $this->invoiceNumberFor($assignment, $periodStart),
                    'billing_period_start' => $periodStart->toDateString(),
                    'billing_period_end' => $periodEnd->toDateString(),
                    'status' => InvoiceStatus::FINALIZED,
                    'currency' => 'EUR',
                    'total_amount' => $lineItems['total_amount'],
                    'amount_paid' => 0,
                    'paid_amount' => 0,
                    'due_date' => $resolvedDueDate,
                    'finalized_at' => now(),
                    'items' => $lineItems['items'],
                    'snapshot_data' => $lineItems['items'],
                    'snapshot_created_at' => now(),
                    'generated_at' => now(),
                    'generated_by' => $actor !== null ? "user:{$actor->id}" : 'bulk_invoices_action',
                    'approval_status' => 'approved',
                    'automation_level' => 'manual',
                    'approved_by' => $actor?->id,
                    'approved_at' => $actor !== null ? now() : null,
                ]);

                $created = $created->push($invoice);
            });

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    protected function invoiceNumberFor(PropertyAssignment $assignment, CarbonInterface $billingPeriodStart): string
    {
        return sprintf(
            'INV-%s-%d-%d',
            $billingPeriodStart->format('Ym'),
            $assignment->property_id,
            $assignment->tenant_user_id,
        );
    }

    protected function normalizeDate(CarbonInterface|string $value): CarbonImmutable
    {
        return $value instanceof CarbonInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value);
    }

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     total_amount: float
     * }
     */
    protected function lineItemsFor(
        PropertyAssignment $assignment,
        CarbonInterface $billingPeriodStart,
        CarbonInterface $billingPeriodEnd,
    ): array {
        $property = $assignment->property;

        if ($property !== null) {
            $items = $this->invoiceLineItemCalculator->handle(
                $property,
                Carbon::parse($billingPeriodStart->toDateTimeString()),
                Carbon::parse($billingPeriodEnd->toDateTimeString()),
            );

            if ($items !== []) {
                return [
                    'items' => $items,
                    'total_amount' => round((float) collect($items)->sum('total'), 2),
                ];
            }
        }

        return $this->generateInvoiceLineItemsAction->handle(
            $assignment,
            $billingPeriodStart,
            $billingPeriodEnd,
        );
    }
}
