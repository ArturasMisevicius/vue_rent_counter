<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchValidateReadingsRequest extends FormRequest
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
            'reading_ids' => 'required|array|min:1|max:100',
            'reading_ids.*' => 'integer|exists:meter_readings,id',
            'options' => 'nullable|array',
            'options.include_performance_metrics' => 'nullable|boolean',
            'options.validation_level' => 'nullable|string|in:basic,comprehensive,strict',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reading_ids.required' => __('validation.batch_readings_required'),
            'reading_ids.max' => __('validation.batch_size_exceeded'),
            'reading_ids.*.exists' => __('validation.reading_not_found'),
        ];
    }
}