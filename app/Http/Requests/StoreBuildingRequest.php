<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingRequest extends FormRequest
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
            'address' => ['required', 'string', 'max:255'],
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
            'address.required' => 'The building address is required.',
            'total_apartments.required' => 'The total number of apartments is required.',
            'total_apartments.integer' => 'The total number of apartments must be a whole number.',
            'total_apartments.min' => 'The building must have at least 1 apartment.',
            'total_apartments.max' => 'The building cannot have more than 1,000 apartments.',
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
