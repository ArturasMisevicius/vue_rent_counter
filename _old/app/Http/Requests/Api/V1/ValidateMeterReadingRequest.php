<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for meter reading validation API.
 */
class ValidateMeterReadingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('reading'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'service_configuration_id' => [
                'sometimes',
                'integer',
                'exists:service_configurations,id',
            ],
            'validation_options' => 'sometimes|array',
            'validation_options.skip_seasonal_validation' => 'boolean',
            'validation_options.strict_mode' => 'boolean',
            'validation_options.include_recommendations' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'service_configuration_id.exists' => __('validation.service_configuration_not_found'),
            'service_configuration_id.integer' => __('validation.service_configuration_invalid_format'),
            'validation_options.array' => __('validation.validation_options_must_be_array'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'service_configuration_id' => __('validation.attributes.service_configuration'),
            'validation_options' => __('validation.attributes.validation_options'),
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation logic if needed
            $serviceConfigId = $this->input('service_configuration_id');
            
            if ($serviceConfigId) {
                $serviceConfig = \App\Models\ServiceConfiguration::find($serviceConfigId);
                
                if ($serviceConfig && !$this->user()->can('view', $serviceConfig)) {
                    $validator->errors()->add(
                        'service_configuration_id',
                        __('validation.unauthorized_service_configuration')
                    );
                }
            }
        });
    }
}