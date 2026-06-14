<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Invoices;

use App\Enums\PaymentMethod;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateManualPaymentRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user()?->isAdminLike() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => ['sometimes', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
            'tenant_comment' => ['nullable', 'string', 'max:1000'],
            'confirm_immediately' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'invoice_id.exists' => ['exists', 'invoice'],
            'amount.required' => ['required', 'payment_amount'],
            'amount.numeric' => ['numeric', 'payment_amount'],
            'amount.gt' => ['gt.numeric', 'payment_amount', ['value' => 0]],
            'currency.size' => ['size.string', 'currency', ['size' => 3]],
            'payment_method.required' => ['required', 'payment_method'],
            'payment_method.enum' => ['enum', 'payment_method'],
            'payment_date.required' => ['required', 'payment_date'],
            'payment_date.date' => ['date', 'payment_date'],
            'reference.max' => ['max.string', 'payment_reference', ['max' => 255]],
            'transaction_id.max' => ['max.string', 'transaction_id', ['max' => 255]],
            'internal_note.max' => ['max.string', 'internal_note', ['max' => 1000]],
            'tenant_comment.max' => ['max.string', 'tenant_comment', ['max' => 1000]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'invoice_id' => $this->translateAttribute('invoice_id'),
            'amount' => $this->translateAttribute('payment_amount'),
            'currency' => $this->translateAttribute('currency'),
            'payment_method' => $this->translateAttribute('payment_method'),
            'payment_date' => $this->translateAttribute('payment_date'),
            'reference' => $this->translateAttribute('payment_reference'),
            'transaction_id' => $this->translateAttribute('transaction_id'),
            'internal_note' => $this->translateAttribute('internal_note'),
            'tenant_comment' => $this->translateAttribute('tenant_comment'),
            'confirm_immediately' => $this->translateAttribute('confirm_immediately'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'invoice_id',
            'amount',
            'currency',
            'payment_method',
            'payment_date',
            'reference',
            'transaction_id',
            'internal_note',
            'tenant_comment',
        ]);

        $this->emptyStringsToNull([
            'invoice_id',
            'currency',
            'reference',
            'transaction_id',
            'internal_note',
            'tenant_comment',
        ]);

        $this->castBooleans([
            'confirm_immediately',
        ]);
    }
}
