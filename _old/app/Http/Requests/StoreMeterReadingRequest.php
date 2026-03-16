<?php

namespace App\Http\Requests;

use App\Models\Meter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMeterReadingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies/gates
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'meter_id' => ['required', 'exists:meters,id'],
            'reading_date' => ['required', 'date', 'before_or_equal:today'],
            'value' => ['required', 'numeric', 'min:0'],
            'zone' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->failed()) {
                return;
            }

            $this->validateMonotonicity($validator);
            $this->validateZoneSupport($validator);
        });
    }

    /**
     * Validate that the reading is not lower than the previous reading.
     * Implements Property 1: Meter reading monotonicity
     * Validates: Requirements 1.2
     */
    protected function validateMonotonicity(Validator $validator): void
    {
        $meter = Meter::find($this->input('meter_id'));
        
        if (!$meter) {
            return;
        }

        $service = app(\App\Services\MeterReadingService::class);
        $previousReading = $service->getPreviousReading($meter, $this->input('zone'));

        if ($previousReading && $this->input('value') < $previousReading->value) {
            $validator->errors()->add(
                'value',
                __('meter_readings.validation.custom.monotonicity_lower', [
                    'previous' => $previousReading->value,
                ])
            );
        }
    }

    /**
     * Validate that zone is only provided for meters that support zones.
     * Implements Property 4: Multi-zone meter reading acceptance
     * Validates: Requirements 1.5
     */
    protected function validateZoneSupport(Validator $validator): void
    {
        $meterId = $this->input('meter_id');
        $zone = $this->input('zone');

        $meter = Meter::find($meterId);

        if (!$meter) {
            return;
        }

        if ($zone && !$meter->supports_zones) {
            $validator->errors()->add(
                'zone',
                __('meter_readings.validation.custom.zone.unsupported')
            );
        }

        if (!$zone && $meter->supports_zones) {
            $validator->errors()->add(
                'zone',
                __('meter_readings.validation.custom.zone.required_for_multi_zone')
            );
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'meter_id.required' => __('meter_readings.validation.meter_id.required'),
            'meter_id.exists' => __('meter_readings.validation.meter_id.exists'),
            'reading_date.required' => __('meter_readings.validation.reading_date.required'),
            'reading_date.date' => __('meter_readings.validation.reading_date.date'),
            'reading_date.before_or_equal' => __('meter_readings.validation.reading_date.before_or_equal'),
            'value.required' => __('meter_readings.validation.value.required'),
            'value.numeric' => __('meter_readings.validation.value.numeric'),
            'value.min' => __('meter_readings.validation.value.min'),
            'zone.string' => __('meter_readings.validation.zone.string'),
            'zone.max' => __('meter_readings.validation.zone.max'),
        ];
    }
}
