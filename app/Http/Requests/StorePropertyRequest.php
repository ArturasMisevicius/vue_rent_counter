<?php

namespace App\Http\Requests;

use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Property::class);
    }

    public function rules(): array
    {
        return [
            'address' => ['required', 'string', 'max:500'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'area_sqm' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'unit_number' => ['nullable', 'string', 'max:50'],
            'building_id' => [
                'nullable',
                'integer',
                Rule::exists('buildings', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'building_id.exists' => 'The selected building must belong to your organization.',
        ];
    }

    public function attributes(): array
    {
        return [
            'area_sqm' => 'area (m²)',
            'unit_number' => 'unit number',
        ];
    }
}
