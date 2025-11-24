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
            'provider_id.required' => __('tariffs.validation.provider_id.required'),
            'provider_id.exists' => __('tariffs.validation.provider_id.exists'),
            'name.required' => __('tariffs.validation.name.required'),
            'name.string' => __('tariffs.validation.name.string'),
            'name.max' => __('tariffs.validation.name.max'),
            'configuration.required' => __('tariffs.validation.configuration.required'),
            'configuration.array' => __('tariffs.validation.configuration.array'),
            'configuration.type.required' => __('tariffs.validation.configuration.type.required'),
            'configuration.type.string' => __('tariffs.validation.configuration.type.string'),
            'configuration.type.in' => __('tariffs.validation.configuration.type.in'),
            'configuration.currency.required' => __('tariffs.validation.configuration.currency.required'),
            'configuration.currency.string' => __('tariffs.validation.configuration.currency.string'),
            'configuration.currency.in' => __('tariffs.validation.configuration.currency.in'),
            'configuration.rate.required_if' => __('tariffs.validation.configuration.rate.required_if'),
            'configuration.rate.numeric' => __('tariffs.validation.configuration.rate.numeric'),
            'configuration.rate.min' => __('tariffs.validation.configuration.rate.min'),
            'configuration.zones.required_if' => __('tariffs.validation.configuration.zones.required_if'),
            'configuration.zones.array' => __('tariffs.validation.configuration.zones.array'),
            'configuration.zones.min' => __('tariffs.validation.configuration.zones.min'),
            'configuration.zones.*.id.required_with' => __('tariffs.validation.configuration.zones.id.required_with'),
            'configuration.zones.*.id.string' => __('tariffs.validation.configuration.zones.id.string'),
            'configuration.zones.*.start.required_with' => __('tariffs.validation.configuration.zones.start.required_with'),
            'configuration.zones.*.start.string' => __('tariffs.validation.configuration.zones.start.string'),
            'configuration.zones.*.start.regex' => __('tariffs.validation.configuration.zones.start.regex'),
            'configuration.zones.*.end.required_with' => __('tariffs.validation.configuration.zones.end.required_with'),
            'configuration.zones.*.end.string' => __('tariffs.validation.configuration.zones.end.string'),
            'configuration.zones.*.end.regex' => __('tariffs.validation.configuration.zones.end.regex'),
            'configuration.zones.*.rate.required_with' => __('tariffs.validation.configuration.zones.rate.required_with'),
            'configuration.zones.*.rate.numeric' => __('tariffs.validation.configuration.zones.rate.numeric'),
            'configuration.zones.*.rate.min' => __('tariffs.validation.configuration.zones.rate.min'),
            'configuration.weekend_logic.string' => __('tariffs.validation.configuration.weekend_logic.string'),
            'configuration.weekend_logic.in' => __('tariffs.validation.configuration.weekend_logic.in'),
            'configuration.fixed_fee.numeric' => __('tariffs.validation.configuration.fixed_fee.numeric'),
            'configuration.fixed_fee.min' => __('tariffs.validation.configuration.fixed_fee.min'),
            'active_from.required' => __('tariffs.validation.active_from.required'),
            'active_from.date' => __('tariffs.validation.active_from.date'),
            'active_until.after' => __('tariffs.validation.active_until.after'),
            'active_until.date' => __('tariffs.validation.active_until.date'),
            'create_new_version.boolean' => __('tariffs.validation.create_new_version.boolean'),
        ];
    }
}
