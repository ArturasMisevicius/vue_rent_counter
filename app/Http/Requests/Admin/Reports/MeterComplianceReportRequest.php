<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Reports;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeterComplianceReportRequest extends FormRequest
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'meter_type' => ['nullable', Rule::in(array_keys(MeterType::options()))],
            'invoice_status' => ['nullable', Rule::in(array_keys(InvoiceStatus::options()))],
            'only_overdue' => ['required', 'boolean'],
            'compliance_state' => ['nullable', Rule::in(['compliant', 'needs_attention', 'missing'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'start_date.required' => ['required', 'start_date'],
            'start_date.date' => ['date', 'start_date'],
            'end_date.required' => ['required', 'end_date'],
            'end_date.date' => ['date', 'end_date'],
            'end_date.after_or_equal' => ['after_or_equal', 'end_date', ['date' => $this->translateAttribute('start_date')]],
            'meter_type.in' => ['in', 'meter_type'],
            'invoice_status.in' => ['in', 'invoice_status'],
            'only_overdue.required' => ['required', 'only_overdue'],
            'only_overdue.boolean' => ['boolean', 'only_overdue'],
            'compliance_state.in' => ['in', 'compliance_state'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'start_date',
            'end_date',
            'meter_type',
            'invoice_status',
            'only_overdue',
            'compliance_state',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'start_date',
            'end_date',
            'meter_type',
            'invoice_status',
            'compliance_state',
        ]);

        $this->emptyStringsToNull([
            'meter_type',
            'invoice_status',
            'compliance_state',
        ]);

        $this->castBooleans([
            'only_overdue',
        ]);
    }
}
