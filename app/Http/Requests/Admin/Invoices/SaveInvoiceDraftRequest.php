<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Invoices;

use App\Enums\InvoiceStatus;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveInvoiceDraftRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->isAdmin() || $user?->isManager()) ?? false;
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
