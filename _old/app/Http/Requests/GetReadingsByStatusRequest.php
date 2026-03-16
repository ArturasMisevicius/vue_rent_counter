<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for filtering meter readings by validation status.
 * 
 * Provides comprehensive validation for status filtering parameters
 * with proper security constraints and user-friendly error messages.
 */
class GetReadingsByStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => 'required|string|in:pending,validated,rejected,requires_review',
            'date_from' => 'nullable|date|before_or_equal:today',
            'date_to' => 'nullable|date|after_or_equal:date_from|before_or_equal:today',
            'input_method' => 'nullable|string|in:manual,photo_ocr,csv_import,api_integration,estimated',
            'meter_ids' => 'nullable|array|max:100',
            'meter_ids.*' => 'integer|exists:meters,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.required' => __('validation.status_required'),
            'status.in' => __('validation.invalid_status'),
            'date_from.before_or_equal' => __('validation.date_from_future'),
            'date_to.after_or_equal' => __('validation.date_to_before_from'),
            'date_to.before_or_equal' => __('validation.date_to_future'),
            'input_method.in' => __('validation.invalid_input_method'),
            'meter_ids.max' => __('validation.too_many_meters'),
            'meter_ids.*.exists' => __('validation.meter_not_found'),
            'per_page.max' => __('validation.per_page_too_large'),
            'per_page.min' => __('validation.per_page_too_small'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'date_from' => __('validation.attributes.date_from'),
            'date_to' => __('validation.attributes.date_to'),
            'input_method' => __('validation.attributes.input_method'),
            'meter_ids' => __('validation.attributes.meter_ids'),
            'per_page' => __('validation.attributes.per_page'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $perPage = $this->input('per_page', 15);
        $page = $this->input('page', 1);

        // Set default values
        $this->merge([
            'per_page' => is_numeric($perPage) ? (int) $perPage : $perPage,
            'page' => is_numeric($page) ? (int) $page : $page,
        ]);

        // Clean up meter_ids array
        if ($this->has('meter_ids') && is_array($this->input('meter_ids'))) {
            $this->merge([
                'meter_ids' => array_filter($this->input('meter_ids'), 'is_numeric')
            ]);
        }
    }
}
