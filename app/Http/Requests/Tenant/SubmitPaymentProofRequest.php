<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\PaymentMethod;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitPaymentProofRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user()?->isTenant() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date', 'before_or_equal:'.now()->toDateString()],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'tenant_comment' => ['nullable', 'string', 'max:1000'],
            'proof_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'amount.required' => ['required', 'payment_amount'],
            'amount.numeric' => ['numeric', 'payment_amount'],
            'amount.gt' => ['gt.numeric', 'payment_amount', ['value' => 0]],
            'payment_date.required' => ['required', 'payment_date'],
            'payment_date.date' => ['date', 'payment_date'],
            'payment_date.before_or_equal' => ['before_or_equal', 'payment_date', ['date' => now()->toDateString()]],
            'payment_method.required' => ['required', 'payment_method'],
            'payment_method.enum' => ['enum', 'payment_method'],
            'reference.max' => ['max.string', 'payment_reference', ['max' => 255]],
            'transaction_id.max' => ['max.string', 'transaction_id', ['max' => 255]],
            'tenant_comment.max' => ['max.string', 'tenant_comment', ['max' => 1000]],
            'proof_file.file' => ['file', 'payment_proof'],
            'proof_file.max' => ['max.file', 'payment_proof', ['max' => 10240]],
            'proof_file.mimes' => ['mimes', 'payment_proof', ['values' => 'pdf, jpg, jpeg, png, webp']],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => $this->translateAttribute('payment_amount'),
            'payment_date' => $this->translateAttribute('payment_date'),
            'payment_method' => $this->translateAttribute('payment_method'),
            'reference' => $this->translateAttribute('payment_reference'),
            'transaction_id' => $this->translateAttribute('transaction_id'),
            'tenant_comment' => $this->translateAttribute('tenant_comment'),
            'proof_file' => $this->translateAttribute('payment_proof'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'amount',
            'payment_date',
            'payment_method',
            'reference',
            'transaction_id',
            'tenant_comment',
        ]);

        $this->emptyStringsToNull([
            'reference',
            'transaction_id',
            'tenant_comment',
        ]);
    }
}
