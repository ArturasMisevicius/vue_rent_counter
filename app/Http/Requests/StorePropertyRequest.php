<?php

declare(strict_types=1);

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
            'building_id.exists' => __('validation.custom_requests.properties.building_must_belong'),
        ];
    }

    public function attributes(): array
    {
        return [
            'area_sqm' => __('validation.custom_requests.properties.attributes.area_sqm'),
            'unit_number' => __('validation.custom_requests.properties.attributes.unit_number'),
        ];
    }
}
