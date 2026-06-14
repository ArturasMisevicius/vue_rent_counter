<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Invoices;

use App\Enums\AuditLogAction;
use App\Filament\Support\Admin\Invoices\FinalizedInvoiceGuard;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\Billing\InvoiceCalculationRows;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Validation\ValidationException;

final class UpdateInvoiceTenantDescriptions
{
    public function __construct(
        private readonly InvoiceCalculationRows $calculationRows,
        private readonly InvoiceService $invoiceService,
        private readonly FinalizedInvoiceGuard $finalizedInvoiceGuard,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{items?: array<int, array{description_for_tenant?: string|null}>}  $attributes
     */
    public function handle(Invoice $invoice, array $attributes, ?User $actor = null): Invoice
    {
        if ($this->finalizedInvoiceGuard->isImmutable($invoice)) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.invoices.validation.tenant_description_locked'),
            ]);
        }

        $items = $this->calculationRows->forInvoice($invoice);
        $submittedItems = is_array($attributes['items'] ?? null) ? $attributes['items'] : [];
        $before = collect($items)
            ->map(fn (array $item): string => (string) ($item['description_for_tenant'] ?? ''))
            ->all();

        foreach ($items as $index => $item) {
            if (! array_key_exists($index, $submittedItems)) {
                continue;
            }

            $description = trim((string) ($submittedItems[$index]['description_for_tenant'] ?? ''));

            if ((bool) ($item['tenant_visible'] ?? true) && $description === '') {
                throw ValidationException::withMessages([
                    "items.{$index}.description_for_tenant" => __('admin.invoices.validation.tenant_description_required'),
                ]);
            }

            $items[$index]['description_for_tenant'] = $description;
        }

        $updatedInvoice = $this->invoiceService->updateDraft($invoice, [
            'items' => $items,
        ]);

        $this->auditLogger->record(
            AuditLogAction::UPDATED,
            $updatedInvoice,
            [
                'context' => [
                    'mutation' => 'invoice.tenant_descriptions_updated',
                ],
                'before' => [
                    'description_for_tenant' => $before,
                ],
                'after' => [
                    'description_for_tenant' => collect($items)
                        ->map(fn (array $item): string => (string) ($item['description_for_tenant'] ?? ''))
                        ->all(),
                ],
            ],
            $actor?->id,
            'Invoice tenant descriptions updated',
        );

        return $updatedInvoice->fresh(['invoiceItems']);
    }

    /**
     * @param  array{items?: array<int, array{description_for_tenant?: string|null}>}  $attributes
     */
    public function __invoke(Invoice $invoice, array $attributes, ?User $actor = null): Invoice
    {
        return $this->handle($invoice, $attributes, $actor);
    }
}
