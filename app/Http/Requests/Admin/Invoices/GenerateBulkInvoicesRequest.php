<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Invoices;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

class GenerateBulkInvoicesRequest extends FormRequest
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
            'billing_period_start' => ['required', 'date'],
            'billing_period_end' => ['required', 'date', 'after_or_equal:billing_period_start'],
            'due_date' => ['required', 'date', 'after_or_equal:billing_period_end'],
            'selected_assignments' => ['sometimes', 'array'],
            'selected_assignments.*' => ['string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'billing_period_start.required' => ['required', 'billing_period_start'],
            'billing_period_start.date' => ['date', 'billing_period_start'],
            'billing_period_end.required' => ['required', 'billing_period_end'],
            'billing_period_end.date' => ['date', 'billing_period_end'],
            'billing_period_end.after_or_equal' => ['after_or_equal', 'billing_period_end', [
                'date' => $this->translateAttribute('billing_period_start'),
            ]],
            'due_date.required' => ['required', 'due_date'],
            'due_date.date' => ['date', 'due_date'],
            'due_date.after_or_equal' => ['after_or_equal', 'due_date', [
                'date' => $this->translateAttribute('billing_period_end'),
            ]],
            'selected_assignments.array' => ['array', 'selected_assignments'],
            'selected_assignments.*.string' => ['string', 'selected_assignments'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'billing_period_start',
            'billing_period_end',
            'due_date',
            'selected_assignments',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'billing_period_start',
            'billing_period_end',
            'due_date',
        ]);
    }
}
