<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Contracts\BillingServiceInterface;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Http\Requests\Admin\Invoices\CreateInvoiceDraftRequest;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;

class CreateInvoiceDraftAction
{
    public function __construct(
        private readonly BillingServiceInterface $billingService,
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Organization $organization, array $attributes, ?User $actor = null): Invoice
    {
        $this->subscriptionLimitGuard->ensureCanWrite($organization);

        /** @var CreateInvoiceDraftRequest $request */
        $request = new CreateInvoiceDraftRequest;
        $validated = $request->validatePayload([
            ...$attributes,
            'organization_id' => $attributes['organization_id'] ?? $organization->id,
        ], $actor ?? auth()->user());

        return $this->billingService->createDraft($organization, $validated, $actor);
    }
}
