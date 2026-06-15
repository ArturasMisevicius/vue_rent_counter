<?php

declare(strict_types=1);

namespace App\View\Components\Shared;

use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InvoiceSummary extends Component
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

    public string $resolvedPeriodDisplay;

    public function __construct(
        public Invoice $invoice,
        mixed $presentation = null,
        ?string $periodDisplay = null,
    ) {
        $this->presentation = is_array($presentation) ? $presentation : [];
        $this->lineItems = $this->presentation['items'] ?? [];
        $this->payments = $this->presentation['payments'] ?? [];
        $this->resolvedPeriodDisplay = $periodDisplay ?: __('tenant.pages.invoices.period', [
            'start' => $this->presentation['billing_period_start_display'] ?? '—',
            'end' => $this->presentation['billing_period_end_display'] ?? '—',
        ]);
    }

    public function render(): View
    {
        return view('components.shared.invoice-summary');
    }
}
