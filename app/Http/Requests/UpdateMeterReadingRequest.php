<?php

namespace App\Http\Requests;

use App\Models\Meter;
use App\Models\MeterReading;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMeterReadingRequest extends FormRequest
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
        $minLength = config('billing.validation.change_reason_min_length', 10);
        $maxLength = config('billing.validation.change_reason_max_length', 500);

        return [
            'value' => ['required', 'numeric', 'min:0'],
            'change_reason' => ['required', 'string', "min:{$minLength}", "max:{$maxLength}"],
            'reading_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'zone' => ['sometimes', 'nullable', 'string', 'max:50'],
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
        });
    }

    /**
     * Validate that the updated reading maintains monotonicity.
     * Implements Property 1: Meter reading monotonicity
     * Validates: Requirements 1.2
     * 
     * Performance: Eager loads meter relationship to prevent N+1 queries
     */
    protected function validateMonotonicity(Validator $validator): void
    {
        $reading = $this->route('reading');
        
        if (!$reading instanceof MeterReading) {
            return;
        }

        // Eager load meter relationship if not already loaded
        // Prevents N+1 when service queries adjacent readings
        if (!$reading->relationLoaded('meter')) {
            $reading->load('meter');
        }

        $newValue = $this->input('value');
        $zone = $this->input('zone', $reading->zone);

        $this->validateAgainstPreviousReading($validator, $reading, $newValue, $zone);
        $this->validateAgainstNextReading($validator, $reading, $newValue, $zone);
    }

    /**
     * Validate that new value is not lower than previous reading.
     *
     * @param Validator $validator
     * @param MeterReading $reading
     * @param float $newValue
     * @param string|null $zone
     * @return void
     */
    protected function validateAgainstPreviousReading(
        Validator $validator,
        MeterReading $reading,
        float $newValue,
        ?string $zone
    ): void {
        $service = app(\App\Services\MeterReadingService::class);
        $previousReading = $service->getAdjacentReading($reading, $zone, 'previous');

        if ($previousReading && $newValue < $previousReading->value) {
            $validator->errors()->add(
                'value',
                __('meter_readings.validation.custom.monotonicity_lower', [
                    'previous' => $previousReading->value,
                ])
            );
        }
    }

    /**
     * Validate that new value is not higher than next reading.
     *
     * @param Validator $validator
     * @param MeterReading $reading
     * @param float $newValue
     * @param string|null $zone
     * @return void
     */
    protected function validateAgainstNextReading(
        Validator $validator,
        MeterReading $reading,
        float $newValue,
        ?string $zone
    ): void {
        $service = app(\App\Services\MeterReadingService::class);
        $nextReading = $service->getAdjacentReading($reading, $zone, 'next');

        if ($nextReading && $newValue > $nextReading->value) {
            $validator->errors()->add(
                'value',
                __('meter_readings.validation.custom.monotonicity_higher', [
                    'next' => $nextReading->value,
                ])
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
            'value.required' => __('meter_readings.validation.value.required'),
            'value.numeric' => __('meter_readings.validation.value.numeric'),
            'value.min' => __('meter_readings.validation.value.min'),
            'change_reason.required' => __('meter_readings.validation.change_reason.required'),
            'change_reason.min' => __('meter_readings.validation.change_reason.min'),
            'change_reason.max' => __('meter_readings.validation.change_reason.max'),
            'reading_date.date' => __('meter_readings.validation.reading_date.date'),
            'reading_date.before_or_equal' => __('meter_readings.validation.reading_date.before_or_equal'),
            'zone.string' => __('meter_readings.validation.zone.string'),
            'zone.max' => __('meter_readings.validation.zone.max'),
        ];
    }
}
