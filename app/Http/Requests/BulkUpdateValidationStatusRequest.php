<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ValidationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateValidationStatusRequest extends FormRequest
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
            'new_status' => [
                'required',
                'string',
                Rule::in(array_column(ValidationStatus::cases(), 'value')),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reading_ids.required' => __('validation.reading_ids_required'),
            'reading_ids.max' => __('validation.bulk_update_size_exceeded'),
            'new_status.required' => __('validation.status_required'),
            'new_status.in' => __('validation.invalid_status'),
        ];
    }
}