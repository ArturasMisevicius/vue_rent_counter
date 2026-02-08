<?php

namespace App\Http\Requests;

use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $property = $this->route('property');
        return $this->user()->can('update', $property);
    }

    public function rules(): array
    {
        return [
            'address' => ['sometimes', 'required', 'string', 'max:500'],
            'type' => ['sometimes', 'required', Rule::enum(PropertyType::class)],
            'area_sqm' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999.99'],
            'unit_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'building_id' => [
                'sometimes',
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
}
