<?php

declare(strict_types=1);

namespace App\View\Components\Tenant;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InvoiceCard extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $presentation;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $lineItems;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $payments;

    /**
     * @var array<string, string>
     */
    public array $paymentMethods;

    public bool $canSubmitPaymentProof;

    public string $resolvedPeriodDisplay;

    /**
     * @param  array<string, mixed>  $paymentForm
     */
    public function __construct(
        public Invoice $invoice,
        mixed $presentation = null,
        ?string $periodDisplay = null,
        public array $paymentForm = [],
    ) {
        $this->presentation = is_array($presentation) ? $presentation : [];
        $this->lineItems = $this->presentation['items'] ?? [];
        $this->payments = $this->presentation['payments'] ?? [];
        $this->paymentMethods = PaymentMethod::options();
        $this->canSubmitPaymentProof = $invoice->outstanding_balance > 0
            && in_array($invoice->effectiveStatus(), [
                InvoiceStatus::FINALIZED,
                InvoiceStatus::PARTIALLY_PAID,
                InvoiceStatus::OVERDUE,
            ], true);
        $this->resolvedPeriodDisplay = $periodDisplay ?: __('tenant.pages.invoices.period', [
            'start' => $this->presentation['billing_period_start_display'] ?? '—',
            'end' => $this->presentation['billing_period_end_display'] ?? '—',
        ]);
    }

    public function render(): View
    {
        return view('components.tenant.invoice-card');
    }
}
