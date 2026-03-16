<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManagerConsumptionReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'property_id' => ['nullable', 'exists:properties,id'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            // Universal service filter:
            // - utility:{id} (preferred)
            // - type:{meter_type} (legacy meters only)
            'service' => ['nullable', 'string', 'max:64'],
            // Backward compatibility (deprecated): meter_type is mapped to service=type:{meter_type}
            'meter_type' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.date' => __('reports.validation.start_date.date'),
            'end_date.date' => __('reports.validation.end_date.date'),
            'end_date.after_or_equal' => __('reports.validation.end_date.after_or_equal'),
            'property_id.exists' => __('reports.validation.property_id.exists'),
        ];
    }
}
