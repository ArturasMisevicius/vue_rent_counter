<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceHistoryFilterRequest extends FormRequest
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
            'selectedStatus' => ['nullable', 'string', Rule::in(['all', 'unpaid', 'paid', 'outstanding'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'selectedStatus.in' => ['in', 'invoice_status_filter'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'selectedStatus' => $this->translateAttribute('invoice_status_filter'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'selectedStatus',
        ]);

        $statusInput = $this->input('selectedStatus');

        $status = match (true) {
            $statusInput === null => 'all',
            ! is_string($statusInput) => $statusInput,
            $statusInput === 'outstanding',
            $statusInput === 'unpaid' => 'unpaid',
            $statusInput === 'paid' => 'paid',
            $statusInput === 'all',
            $statusInput === '' => 'all',
            default => $statusInput,
        };

        $this->merge([
            'selectedStatus' => $status,
        ]);
    }
}
