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
            'tenant_id' => [
                'required', 
                'integer',
                'exists:organizations,id'
            ],
            'name' => [
                'required', 
                'string', 
                'max:255',
                'regex:/^[a-zA-Z0-9\s\-_\.#]+$/', // Allow alphanumeric, spaces, hyphens, underscores, dots, hash
                \Illuminate\Validation\Rule::unique('buildings')->where('tenant_id', $this->input('tenant_id'))
            ],
            'address' => [
                'required', 
                'string', 
                'max:500', // Increased for full addresses
                'regex:/^[a-zA-Z0-9\s\-_\.,#\/]+$/' // Allow common address characters
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-ZąčęėįšųūžĄČĘĖĮŠŲŪŽ\s\-]+$/' // Allow Lithuanian characters
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[A-Z0-9\s\-]+$/i'
            ],
            'country' => [
                'nullable',
                'string',
                'size:2',
                'uppercase'
            ],
            'total_apartments' => [
                'required', 
                'integer', 
                'min:1', 
                'max:1000'
            ],
            'floors' => [
                'nullable',
                'integer',
                'min:1',
                'max:100'
            ],
            'built_year' => [
                'nullable',
                'integer',
                'min:1800',
                'max:' . (date('Y') + 5)
            ],
            'heating_type' => [
                'nullable',
                'string',
                'in:central,individual,electric,gas,other'
            ],
            'elevator' => [
                'nullable',
                'boolean'
            ],
            'parking_spaces' => [
                'nullable',
                'integer',
                'min:0',
                'max:500'
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000'
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
            'tenant_id.required' => __('buildings.validation.tenant_id.required'),
            'tenant_id.integer' => __('buildings.validation.tenant_id.integer'),
            'tenant_id.exists' => __('buildings.validation.tenant_id.exists'),
            'name.required' => __('buildings.validation.name.required'),
            'name.string' => __('buildings.validation.name.string'),
            'name.max' => __('buildings.validation.name.max'),
            'name.regex' => __('buildings.validation.name.regex'),
            'name.unique' => __('buildings.validation.name.unique'),
            'address.required' => __('buildings.validation.address.required'),
            'address.string' => __('buildings.validation.address.string'),
            'address.max' => __('buildings.validation.address.max'),
            'address.regex' => __('buildings.validation.address.regex'),
            'city.regex' => __('buildings.validation.city.regex'),
            'postal_code.regex' => __('buildings.validation.postal_code.regex'),
            'country.size' => __('buildings.validation.country.size'),
            'total_apartments.required' => __('buildings.validation.total_apartments.required'),
            'total_apartments.integer' => __('buildings.validation.total_apartments.integer'),
            'total_apartments.min' => __('buildings.validation.total_apartments.min'),
            'total_apartments.max' => __('buildings.validation.total_apartments.max'),
            'built_year.min' => __('buildings.validation.built_year.min'),
            'built_year.max' => __('buildings.validation.built_year.max'),
            'heating_type.in' => __('buildings.validation.heating_type.in'),
            'parking_spaces.max' => __('buildings.validation.parking_spaces.max'),
            'notes.max' => __('buildings.validation.notes.max'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tenant_id' => __('buildings.attributes.tenant_id'),
            'name' => __('buildings.attributes.name'),
            'address' => __('buildings.attributes.address'),
            'city' => __('buildings.attributes.city'),
            'postal_code' => __('buildings.attributes.postal_code'),
            'country' => __('buildings.attributes.country'),
            'total_apartments' => __('buildings.attributes.total_apartments'),
            'floors' => __('buildings.attributes.floors'),
            'built_year' => __('buildings.attributes.built_year'),
            'heating_type' => __('buildings.attributes.heating_type'),
            'elevator' => __('buildings.attributes.elevator'),
            'parking_spaces' => __('buildings.attributes.parking_spaces'),
            'notes' => __('buildings.attributes.notes'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Automatically set tenant_id from authenticated user and normalize data
        $this->merge([
            'tenant_id' => auth()->user()->tenant_id,
            'country' => $this->country ?? 'LT', // Default to Lithuania
            'name' => trim($this->name ?? ''),
            'address' => trim($this->address ?? ''),
            'city' => trim($this->city ?? ''),
        ]);
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Additional business logic validation
        if ($this->floors && $this->total_apartments) {
            $avgApartmentsPerFloor = $this->total_apartments / $this->floors;
            if ($avgApartmentsPerFloor > 20) {
                $this->validator->errors()->add(
                    'total_apartments', 
                    __('buildings.validation.apartments_per_floor_excessive')
                );
            }
        }

        if ($this->built_year && $this->built_year < 1900 && $this->heating_type === 'central') {
            $this->validator->errors()->add(
                'heating_type',
                __('buildings.validation.central_heating_anachronistic')
            );
        }
    }
}
