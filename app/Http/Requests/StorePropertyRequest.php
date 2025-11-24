<?php

namespace App\Http\Requests;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
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
            'address' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'area_sqm' => ['required', 'numeric', 'min:0', 'max:10000'],
            'building_id' => ['nullable', 'exists:buildings,id'],
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
            'tenant_id.required' => __('properties.validation.tenant_id.required'),
            'tenant_id.integer' => __('properties.validation.tenant_id.integer'),
            'address.required' => __('properties.validation.address.required'),
            'address.max' => __('properties.validation.address.max'),
            'address.string' => __('properties.validation.address.string'),
            'type.required' => __('properties.validation.type.required'),
            'type.enum' => __('properties.validation.type.enum'),
            'area_sqm.required' => __('properties.validation.area_sqm.required'),
            'area_sqm.numeric' => __('properties.validation.area_sqm.numeric'),
            'area_sqm.min' => __('properties.validation.area_sqm.min'),
            'area_sqm.max' => __('properties.validation.area_sqm.max'),
            'building_id.exists' => __('properties.validation.building_id.exists'),
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
        ]);
    }
}
