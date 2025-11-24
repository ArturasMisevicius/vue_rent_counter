<?php

namespace App\Http\Requests;

use App\Enums\MeterType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeterRequest extends FormRequest
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
        $meter = $this->route('meter');
        
        return [
            'tenant_id' => ['required', 'integer'],
            'serial_number' => ['required', 'string', 'max:255', Rule::unique('meters', 'serial_number')->ignore($meter->id)],
            'type' => ['required', Rule::enum(MeterType::class)],
            'property_id' => ['required', 'exists:properties,id'],
            'installation_date' => ['required', 'date', 'before_or_equal:today'],
            'supports_zones' => ['boolean'],
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
        $this->merge([
            'tenant_id' => $this->user()?->tenant_id ?? $this->input('tenant_id'),
            'supports_zones' => $this->boolean('supports_zones', false),
        ]);
    }
}
