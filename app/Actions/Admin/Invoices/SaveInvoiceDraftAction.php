<?php

namespace App\Actions\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\BillingRecord;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveInvoiceDraftAction
{
    public function __construct(
        private readonly GenerateInvoiceLineItemsAction $generateInvoiceLineItemsAction,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Organization $organization, array $attributes, User $actor): Invoice
    {
        $periodStart = Carbon::parse($attributes['billing_period_start'])->startOfDay();
        $periodEnd = Carbon::parse($attributes['billing_period_end'])->endOfDay();

        $property = Property::query()
            ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number'])
            ->where('organization_id', $organization->id)
            ->findOrFail($attributes['property_id']);

        $invoice = isset($attributes['invoice_id'])
            ? Invoice::query()->where('organization_id', $organization->id)->findOrFail($attributes['invoice_id'])
            : new Invoice;

        if ($invoice->exists && $invoice->status !== InvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.invoices.messages.finalized_locked'),
            ]);
        }

        $items = $attributes['items'] ?? $this->generateInvoiceLineItemsAction->handle($property, $periodStart, $periodEnd);
        $total = round((float) collect($items)->sum('total'), 2);

        return DB::transaction(function () use (
            $invoice,
            $organization,
            $attributes,
            $actor,
            $periodStart,
            $periodEnd,
            $items,
            $total,
        ): Invoice {
            $invoice->fill([
                'organization_id' => $organization->id,
                'property_id' => $attributes['property_id'],
                'tenant_user_id' => $attributes['tenant_user_id'] ?? null,
                'invoice_number' => $invoice->invoice_number ?: $this->nextInvoiceNumber(),
                'billing_period_start' => $periodStart->toDateString(),
                'billing_period_end' => $periodEnd->toDateString(),
                'status' => InvoiceStatus::DRAFT,
                'currency' => 'EUR',
                'total_amount' => $total,
                'amount_paid' => $invoice->amount_paid ?? 0,
                'paid_amount' => $invoice->paid_amount ?? 0,
                'due_date' => $attributes['due_date'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'generated_at' => now(),
                'generated_by' => (string) $actor->id,
                'items' => $items,
            ]);
            $invoice->save();

            $invoice->invoiceItems()->delete();
            $invoice->billingRecords()->delete();

            foreach ($items as $item) {
                $invoice->invoiceItems()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                    'meter_reading_snapshot' => $item['meter_reading_snapshot'] ?? null,
                ]);

                if (! isset($item['utility_service_id'])) {
                    continue;
                }

                BillingRecord::query()->create([
                    'organization_id' => $organization->id,
                    'property_id' => $attributes['property_id'],
                    'utility_service_id' => $item['utility_service_id'],
                    'invoice_id' => $invoice->id,
                    'tenant_user_id' => $attributes['tenant_user_id'] ?? null,
                    'amount' => $item['total'],
                    'consumption' => $item['consumption'] ?? null,
                    'rate' => $item['rate'] ?? null,
                    'meter_reading_start' => $item['meter_reading_snapshot']['start']['id'] ?? null,
                    'meter_reading_end' => $item['meter_reading_snapshot']['end']['id'] ?? null,
                    'billing_period_start' => $periodStart->toDateString(),
                    'billing_period_end' => $periodEnd->toDateString(),
                    'notes' => $invoice->notes,
                ]);
            }

            return $invoice->fresh(['invoiceItems', 'billingRecords']);
        });
    }

    private function nextInvoiceNumber(): string
    {
        $nextId = (int) Invoice::query()->max('id') + 1;

        return sprintf('INV-%s-%04d', now()->format('Ym'), $nextId);
    }
}
