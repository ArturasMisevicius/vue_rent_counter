<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Properties;

use App\Enums\PropertyType;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private ?int $organizationId = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->isSuperadmin() || $user?->isAdmin() || $user?->isManager()) ?? false;
    }

    public function forOrganization(int $organizationId): self
    {
        $request = clone $this;
        $request->organizationId = $organizationId;

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'building_id' => [
                'required',
                'integer',
                Rule::exists('buildings', 'id')->where(
                    fn ($query) => $query->where('organization_id', $this->organizationId),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit_number' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'floor_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'building_id.required' => ['required', 'building'],
            'building_id.integer' => ['integer', 'building'],
            'building_id.exists' => ['exists', 'building'],
            'name.required' => ['required', 'name'],
            'name.max' => ['max.string', 'name', ['max' => 255]],
            'unit_number.required' => ['required', 'unit_number'],
            'unit_number.max' => ['max.string', 'unit_number', ['max' => 50]],
            'type.required' => ['required', 'property_type'],
            'type.enum' => ['enum', 'property_type'],
            'floor_area_sqm.numeric' => ['numeric', 'floor_area_sqm'],
            'floor_area_sqm.min' => ['min.numeric', 'floor_area_sqm', ['min' => 0]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'building_id' => $this->translateAttribute('building'),
            'type' => $this->translateAttribute('property_type'),
            ...$this->translatedAttributes([
                'name',
                'unit_number',
                'floor_area_sqm',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'building_id',
            'name',
            'unit_number',
            'type',
            'floor_area_sqm',
        ]);

        $this->emptyStringsToNull([
            'floor_area_sqm',
        ]);

        $type = $this->input('type');

        if ($type instanceof PropertyType) {
            $this->merge([
                'type' => $type->value,
            ]);
        }
    }
}
