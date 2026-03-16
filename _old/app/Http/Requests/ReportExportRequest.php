<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportExportRequest extends FormRequest
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
            'report_type' => ['required', 'in:consumption,revenue,outstanding,meter-readings'],
            'format' => ['required', 'in:csv,excel,pdf'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
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
            'report_type.required' => __('reports.validation.report_type.required'),
            'report_type.in' => __('reports.validation.report_type.in'),
            'format.required' => __('reports.validation.format.required'),
            'format.in' => __('reports.validation.format.in'),
            'start_date.date' => __('reports.validation.start_date.date'),
            'end_date.date' => __('reports.validation.end_date.date'),
        ];
    }
}
