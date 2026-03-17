<?php

namespace App\Filament\Actions\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Admin\Invoices\FinalizedInvoiceGuard;
use App\Models\Invoice;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SaveInvoiceDraftAction
{
    public function __construct(
        private readonly FinalizedInvoiceGuard $finalizedInvoiceGuard,
    ) {}

    public function handle(Invoice $invoice, array $attributes): Invoice
    {
        $normalized = $this->normalizeAttributes($attributes);

        $this->finalizedInvoiceGuard->ensureCanMutate($invoice, $normalized);

        $validated = $this->validate($normalized);

        if (! $this->finalizedInvoiceGuard->isImmutable($invoice) && ! array_key_exists('status', $validated)) {
            $validated['status'] = InvoiceStatus::DRAFT;
        }

        $invoice->update($validated);

        return $invoice->fresh();
    }

    private function normalizeAttributes(array $attributes): array
    {
        if (($attributes['status'] ?? null) instanceof InvoiceStatus) {
            $attributes['status'] = $attributes['status']->value;
        }

        if (array_key_exists('items', $attributes) && is_string($attributes['items'])) {
            $trimmedItems = trim($attributes['items']);

            if ($trimmedItems === '') {
                $attributes['items'] = null;
            } else {
                $decodedItems = json_decode($trimmedItems, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $attributes['items'] = $decodedItems;
                }
            }
        }

        if (array_key_exists('items', $attributes) && is_array($attributes['items'])) {
            $attributes['items'] = array_map(function (mixed $item): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                if (array_key_exists('amount', $item)) {
                    $item['amount'] = round((float) $item['amount'], 2);
                }

                return $item;
            }, $attributes['items']);
        }

        return Arr::only($attributes, [
            'invoice_number',
            'billing_period_start',
            'billing_period_end',
            'status',
            'total_amount',
            'amount_paid',
            'paid_amount',
            'due_date',
            'paid_at',
            'payment_reference',
            'items',
            'notes',
        ]);
    }

    private function validate(array $attributes): array
    {
        /** @var array<string, mixed> $validated */
        $validated = Validator::make($attributes, [
            'invoice_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billing_period_start' => ['sometimes', 'nullable', 'date'],
            'billing_period_end' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::enum(InvoiceStatus::class)],
            'total_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'amount_paid' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'paid_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
            'payment_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items' => ['sometimes', 'nullable', 'array'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ])->validate();

        return $validated;
    }
}
