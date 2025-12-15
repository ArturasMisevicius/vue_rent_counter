<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\\s\\-_\\.#]+$/',
            ],
            'address' => [
                'required',
                'string',
                'max:500',
                'regex:/^[a-zA-Z0-9\\s\\-_\\.,#\\/]+$/',
            ],
            'total_apartments' => ['required', 'integer', 'min:1', 'max:1000'],
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
            'tenant_id.required' => __('buildings.validation.tenant_id.required'),
            'tenant_id.integer' => __('buildings.validation.tenant_id.integer'),
            'name.required' => __('buildings.validation.name.required'),
            'name.string' => __('buildings.validation.name.string'),
            'name.max' => __('buildings.validation.name.max'),
            'name.regex' => __('buildings.validation.name.regex'),
            'address.required' => __('buildings.validation.address.required'),
            'address.string' => __('buildings.validation.address.string'),
            'address.max' => __('buildings.validation.address.max'),
            'address.regex' => __('buildings.validation.address.regex'),
            'total_apartments.required' => __('buildings.validation.total_apartments.required'),
            'total_apartments.integer' => __('buildings.validation.total_apartments.integer'),
            'total_apartments.min' => __('buildings.validation.total_apartments.min'),
            'total_apartments.max' => __('buildings.validation.total_apartments.max'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_id' => $this->user()?->tenant_id ?? $this->input('tenant_id'),
        ]);
    }
}
