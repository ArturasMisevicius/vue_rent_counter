<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Contracts\BillingServiceInterface;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class GenerateBulkInvoicesAction
{
    public function __construct(
        protected BillingServiceInterface $billingService,
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
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
        $this->subscriptionLimitGuard->ensureCanWrite($organization);

        if (is_array($billingPeriodStart)) {
            $resolvedActor = $billingPeriodEnd instanceof User ? $billingPeriodEnd : $actor;

            return $this->handleAttributes($organization, $billingPeriodStart, $resolvedActor);
        }

        if (! $billingPeriodEnd instanceof CarbonInterface && ! is_string($billingPeriodEnd)) {
            throw new InvalidArgumentException('The billing period end date must be a string or Carbon instance.');
        }

        return $this->billingService->generateBulkInvoices(
            $organization,
            [
                'billing_period_start' => $billingPeriodStart instanceof CarbonInterface
                    ? $billingPeriodStart->toDateString()
                    : $billingPeriodStart,
                'billing_period_end' => $billingPeriodEnd instanceof CarbonInterface
                    ? $billingPeriodEnd->toDateString()
                    : $billingPeriodEnd,
            ],
            $actor,
        )['created'];
    }

    /**
     * @param  array{billing_period_start: string, billing_period_end: string, due_date?: string, selected_assignments?: array<int, string>}  $attributes
     * @return array{created: Collection<int, Invoice>, skipped: array<int, array{tenant_id: int, property_id: int, reason: string}>}
     */
    protected function handleAttributes(Organization $organization, array $attributes, ?User $actor = null): array
    {
        return $this->billingService->generateBulkInvoices(
            $organization,
            [
                'billing_period_start' => $attributes['billing_period_start'],
                'billing_period_end' => $attributes['billing_period_end'],
                'due_date' => $attributes['due_date'] ?? null,
                'selected_assignments' => $attributes['selected_assignments'] ?? [],
            ],
            $actor,
        );
    }
}
