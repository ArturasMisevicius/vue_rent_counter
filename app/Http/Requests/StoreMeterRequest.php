<?php

namespace App\Http\Requests;

use App\Enums\MeterType;
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
            'serial_number' => ['required', 'string', 'max:255', 'unique:meters,serial_number'],
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
            'serial_number.required' => 'The meter serial number is required.',
            'serial_number.unique' => 'This serial number is already registered.',
            'type.required' => 'The meter type is required.',
            'type.enum' => 'The meter type must be a valid type (electricity, water_cold, water_hot, heating).',
            'property_id.required' => 'The property is required.',
            'property_id.exists' => 'The selected property does not exist.',
            'installation_date.required' => 'The installation date is required.',
            'installation_date.before_or_equal' => 'The installation date cannot be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Automatically set tenant_id from authenticated user
        $this->merge([
            'tenant_id' => auth()->user()->tenant_id,
            'supports_zones' => $this->boolean('supports_zones', false),
        ]);
    }
}
