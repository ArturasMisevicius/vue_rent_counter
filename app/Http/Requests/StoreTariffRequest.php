<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTariffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies/gates
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If configuration is a JSON string, decode it to an array
        if ($this->has('configuration') && is_string($this->configuration)) {
            $decoded = json_decode($this->configuration, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge([
                    'configuration' => $decoded,
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider_id' => ['required', 'exists:providers,id'],
            'name' => ['required', 'string', 'max:255'],
            'configuration' => ['required', 'array'],
            'configuration.type' => ['required', 'string', 'in:flat,time_of_use'],
            'configuration.currency' => ['required', 'string', 'in:EUR'],
            'configuration.rate' => ['required_if:configuration.type,flat', 'numeric', 'min:0'],
            'configuration.zones' => ['required_if:configuration.type,time_of_use', 'array', 'min:1'],
            'configuration.zones.*.id' => ['required_with:configuration.zones', 'string'],
            'configuration.zones.*.start' => ['required_with:configuration.zones', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            'configuration.zones.*.end' => ['required_with:configuration.zones', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            'configuration.zones.*.rate' => ['required_with:configuration.zones', 'numeric', 'min:0'],
            'configuration.weekend_logic' => ['sometimes', 'nullable', 'string', 'in:apply_night_rate,apply_day_rate,apply_weekend_rate'],
            'configuration.fixed_fee' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'active_from' => ['required', 'date'],
            'active_until' => ['nullable', 'date', 'after:active_from'],
            'create_new_version' => ['nullable', 'boolean'],
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

            $configuration = $this->input('configuration');

            if (isset($configuration['type']) && $configuration['type'] === 'time_of_use') {
                $this->validateTimeOfUseZones($validator, $configuration);
            }
        });
    }

    /**
     * Validate time-of-use zones for overlaps and 24-hour coverage.
     * Implements Property 6: Time-of-use zone validation
     * Validates: Requirements 2.2
     */
    protected function validateTimeOfUseZones(Validator $validator, array $configuration): void
    {
        if (!isset($configuration['zones']) || !is_array($configuration['zones'])) {
            return;
        }

        $timeRangeValidator = app(\App\Services\TimeRangeValidator::class);
        $errors = $timeRangeValidator->validate($configuration['zones']);

        foreach ($errors as $error) {
            $validator->errors()->add('configuration.zones', $error);
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
            'provider_id.required' => 'Provider is required',
            'provider_id.exists' => 'Selected provider does not exist',
            'name.required' => 'Tariff name is required',
            'configuration.required' => 'Tariff configuration is required',
            'configuration.type.required' => 'Tariff type is required',
            'configuration.type.in' => 'Tariff type must be either flat or time_of_use',
            'configuration.currency.required' => 'Currency is required',
            'configuration.currency.in' => 'Currency must be EUR',
            'configuration.rate.required_if' => 'Rate is required for flat tariffs',
            'configuration.zones.required_if' => 'Zones are required for time-of-use tariffs',
            'configuration.zones.min' => 'At least one zone is required for time-of-use tariffs',
            'configuration.zones.*.start.regex' => 'Zone start time must be in HH:MM format (24-hour)',
            'configuration.zones.*.end.regex' => 'Zone end time must be in HH:MM format (24-hour)',
            'configuration.zones.*.rate.required_with' => 'Rate is required for each zone',
            'configuration.zones.*.rate.min' => 'Zone rate must be a positive number',
            'active_from.required' => 'Active from date is required',
            'active_until.after' => 'Active until date must be after active from date',
        ];
    }
}
