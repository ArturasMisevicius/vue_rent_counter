<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Invoices;

use App\Enums\PaymentMethod;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
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
            'amount_paid' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'paid_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'method' => ['sometimes', Rule::enum(PaymentMethod::class)],
            'payment_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'amount_paid.numeric' => ['numeric', 'amount_paid'],
            'amount_paid.min' => ['min.numeric', 'amount_paid', ['min' => 0]],
            'paid_amount.numeric' => ['numeric', 'paid_amount'],
            'paid_amount.min' => ['min.numeric', 'paid_amount', ['min' => 0]],
            'method.enum' => ['enum', 'method'],
            'payment_reference.max' => ['max.string', 'payment_reference', ['max' => 255]],
            'paid_at.date' => ['date', 'paid_at'],
            'notes.max' => ['max.string', 'notes', ['max' => 1000]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'amount_paid',
            'paid_amount',
            'method',
            'payment_reference',
            'paid_at',
            'notes',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'amount_paid',
            'paid_amount',
            'method',
            'payment_reference',
            'paid_at',
            'notes',
        ]);

        $this->emptyStringsToNull([
            'amount_paid',
            'paid_amount',
            'method',
            'payment_reference',
            'paid_at',
            'notes',
        ]);
    }
}
