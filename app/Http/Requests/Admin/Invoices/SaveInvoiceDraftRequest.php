<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Filament\Support\Billing\InvoiceLineItemDescription;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveInvoiceDraftRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isAdminLike() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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
            'items.*.source_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'items.*.source_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.service_configuration_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.utility_service_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.tariff_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.provider_id' => ['sometimes', 'nullable', 'integer'],
            'items.*.title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.description' => ['sometimes', 'nullable', 'string', 'max:'.InvoiceLineItemDescription::MAX_LENGTH],
            'items.*.description_for_tenant' => ['sometimes', 'nullable', 'string', 'max:'.InvoiceLineItemDescription::MAX_LENGTH],
            'items.*.internal_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'items.*.period' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.quantity' => ['sometimes', 'nullable', 'numeric'],
            'items.*.unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['sometimes', 'nullable', 'numeric'],
            'items.*.subtotal' => ['sometimes', 'nullable', 'numeric'],
            'items.*.tax_amount' => ['sometimes', 'nullable', 'numeric'],
            'items.*.discount_amount' => ['sometimes', 'nullable', 'numeric'],
            'items.*.amount' => ['sometimes', 'nullable', 'numeric'],
            'items.*.total' => ['sometimes', 'nullable', 'numeric'],
            'items.*.currency' => ['sometimes', 'nullable', 'string', 'size:3'],
            'items.*.formula_label' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items.*.calculation_snapshot' => ['sometimes', 'nullable', 'array'],
            'items.*.tenant_visible' => ['sometimes', 'boolean'],
            'items.*.sort_order' => ['sometimes', 'nullable', 'integer'],
            'items.*.consumption' => ['sometimes', 'nullable', 'numeric'],
            'items.*.rate' => ['sometimes', 'nullable', 'numeric'],
            'items.*.is_adjustment' => ['sometimes', 'boolean'],
            'items.*.meter_reading_snapshot' => ['sometimes', 'nullable', 'array'],
            'items.*.service_snapshot' => ['sometimes', 'nullable', 'array'],
            'items.*.tariff_snapshot' => ['sometimes', 'nullable', 'array'],
            'items.*.provider_snapshot' => ['sometimes', 'nullable', 'array'],
            'items.*.billable' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'invoice_number.max' => ['max.string', 'invoice_number', ['max' => 255]],
            'billing_period_start.date' => ['date', 'billing_period_start'],
            'billing_period_end.date' => ['date', 'billing_period_end'],
            'status.enum' => ['enum', 'invoice_status'],
            'total_amount.numeric' => ['numeric', 'total_amount'],
            'total_amount.min' => ['min.numeric', 'total_amount', ['min' => 0]],
            'amount_paid.numeric' => ['numeric', 'amount_paid'],
            'amount_paid.min' => ['min.numeric', 'amount_paid', ['min' => 0]],
            'paid_amount.numeric' => ['numeric', 'paid_amount'],
            'paid_amount.min' => ['min.numeric', 'paid_amount', ['min' => 0]],
            'due_date.date' => ['date', 'due_date'],
            'paid_at.date' => ['date', 'paid_at'],
            'payment_reference.max' => ['max.string', 'payment_reference', ['max' => 255]],
            'items.array' => ['array', 'items'],
            'items.*.description.max' => ['max.string', 'items', ['max' => InvoiceLineItemDescription::MAX_LENGTH]],
            'items.*.quantity.numeric' => ['numeric', 'items'],
            'items.*.unit_price.numeric' => ['numeric', 'items'],
            'items.*.amount.numeric' => ['numeric', 'items'],
            'items.*.total.numeric' => ['numeric', 'items'],
            'items.*.consumption.numeric' => ['numeric', 'items'],
            'items.*.rate.numeric' => ['numeric', 'items'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => $this->translateAttribute('invoice_status'),
            ...$this->translatedAttributes([
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'total_amount',
                'amount_paid',
                'paid_amount',
                'due_date',
                'paid_at',
                'payment_reference',
                'items',
                'notes',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
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
            'notes',
        ]);

        $this->emptyStringsToNull([
            'invoice_number',
            'billing_period_start',
            'billing_period_end',
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
}
