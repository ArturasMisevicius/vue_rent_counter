<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Policies applied in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', 'in:electricity,water,heating'],
            'contact_info' => ['nullable', 'string'],
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
            'name.required' => __('providers.validation.name.required'),
            'name.string' => __('providers.validation.name.string'),
            'name.max' => __('providers.validation.name.max'),
            'service_type.required' => __('providers.validation.service_type.required'),
            'service_type.in' => __('providers.validation.service_type.in'),
            'contact_info.string' => __('providers.validation.contact_info.string'),
        ];
    }
}
