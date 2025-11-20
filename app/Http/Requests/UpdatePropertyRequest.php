<?php

namespace App\Http\Requests;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
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
            'address.required' => 'The property address is required.',
            'type.required' => 'The property type is required.',
            'type.enum' => 'The property type must be either apartment or house.',
            'area_sqm.required' => 'The property area is required.',
            'area_sqm.numeric' => 'The property area must be a number.',
            'area_sqm.min' => 'The property area must be at least 0 square meters.',
            'area_sqm.max' => 'The property area cannot exceed 10,000 square meters.',
            'building_id.exists' => 'The selected building does not exist.',
        ];
    }
}
