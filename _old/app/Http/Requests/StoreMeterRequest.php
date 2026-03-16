<?php

namespace App\Http\Requests;

use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Models\ServiceConfiguration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:meters,serial_number'],
            'type' => ['required', Rule::enum(MeterType::class)],
            'property_id' => ['required', 'exists:properties,id'],
            'installation_date' => ['required', 'date', 'before_or_equal:today'],
            'supports_zones' => ['boolean'],
            'service_configuration_id' => [
                'nullable',
                'integer',
                Rule::exists('service_configurations', 'id')->where(function ($query) {
                    $tenantId = $this->user()?->tenant_id;
                    $propertyId = $this->input('property_id');

                    if ($tenantId !== null) {
                        $query->where('tenant_id', $tenantId);
                    }

                    if ($propertyId) {
                        $query->where('property_id', $propertyId);
                    }

                    $query->where('is_active', true);
                }),
            ],
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
            'tenant_id.required' => __('meters.validation.tenant_id.required'),
            'tenant_id.integer' => __('meters.validation.tenant_id.integer'),
            'serial_number.required' => __('meters.validation.serial_number.required'),
            'serial_number.unique' => __('meters.validation.serial_number.unique'),
            'serial_number.string' => __('meters.validation.serial_number.string'),
            'serial_number.max' => __('meters.validation.serial_number.max'),
            'type.required' => __('meters.validation.type.required'),
            'type.enum' => __('meters.validation.type.enum_detail'),
            'property_id.required' => __('meters.validation.property_id.required'),
            'property_id.exists' => __('meters.validation.property_id.exists'),
            'installation_date.required' => __('meters.validation.installation_date.required'),
            'installation_date.date' => __('meters.validation.installation_date.date'),
            'installation_date.before_or_equal' => __('meters.validation.installation_date.before_or_equal'),
            'supports_zones.boolean' => __('meters.validation.supports_zones.boolean'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $serviceConfigurationId = $this->input('service_configuration_id');
        $serviceConfigurationId = $serviceConfigurationId === null || $serviceConfigurationId === ''
            ? null
            : (int) $serviceConfigurationId;

        // Automatically set tenant_id from authenticated user
        $this->merge([
            'tenant_id' => auth()->user()->tenant_id,
            'supports_zones' => $this->boolean('supports_zones', false),
            'service_configuration_id' => $serviceConfigurationId,
        ]);

        if ($serviceConfigurationId !== null) {
            $serviceConfiguration = ServiceConfiguration::query()
                ->whereKey($serviceConfigurationId)
                ->where('tenant_id', auth()->user()->tenant_id)
                ->first();

            if ($serviceConfiguration) {
                $this->merge([
                    'type' => MeterType::CUSTOM->value,
                    'supports_zones' => $serviceConfiguration->pricing_model === PricingModel::TIME_OF_USE,
                ]);
            }
        }
    }
}
