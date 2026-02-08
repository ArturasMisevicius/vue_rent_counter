<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkMeterReadingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller/policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'readings' => ['required', 'array'],
            'readings.*.meter_id' => ['required', 'exists:meters,id'],
            'readings.*.reading_date' => ['required', 'date', 'before_or_equal:today'],
            'readings.*.value' => ['required', 'numeric', 'min:0'],
            'readings.*.zone' => ['nullable', 'string', 'max:50'],
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
            'readings.required' => __('meter_readings.validation.bulk.readings.required'),
            'readings.array' => __('meter_readings.validation.bulk.readings.array'),
            'readings.*.meter_id.required' => __('meter_readings.validation.meter_id.required'),
            'readings.*.meter_id.exists' => __('meter_readings.validation.meter_id.exists'),
            'readings.*.reading_date.required' => __('meter_readings.validation.reading_date.required'),
            'readings.*.reading_date.date' => __('meter_readings.validation.reading_date.date'),
            'readings.*.reading_date.before_or_equal' => __('meter_readings.validation.reading_date.before_or_equal'),
            'readings.*.value.required' => __('meter_readings.validation.value.required'),
            'readings.*.value.numeric' => __('meter_readings.validation.value.numeric'),
            'readings.*.value.min' => __('meter_readings.validation.value.min'),
            'readings.*.zone.string' => __('meter_readings.validation.zone.string'),
            'readings.*.zone.max' => __('meter_readings.validation.zone.max'),
        ];
    }
}
