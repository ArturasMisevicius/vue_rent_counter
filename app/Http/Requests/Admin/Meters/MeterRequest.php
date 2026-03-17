<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Meters;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeterRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private ?int $organizationId = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->isAdmin() || $user?->isManager()) ?? false;
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
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id')->where(
                    fn ($query) => $query->where('organization_id', $this->organizationId),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(MeterType::class)],
            'unit' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::enum(MeterStatus::class)],
            'installed_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'property_id.required' => ['required', 'property'],
            'property_id.integer' => ['integer', 'property'],
            'property_id.exists' => ['exists', 'property'],
            'name.required' => ['required', 'name'],
            'name.max' => ['max.string', 'name', ['max' => 255]],
            'identifier.required' => ['required', 'identifier'],
            'identifier.max' => ['max.string', 'identifier', ['max' => 255]],
            'type.required' => ['required', 'meter_type'],
            'type.enum' => ['enum', 'meter_type'],
            'unit.max' => ['max.string', 'unit', ['max' => 50]],
            'status.required' => ['required', 'status'],
            'status.enum' => ['enum', 'status'],
            'installed_at.date' => ['date', 'installed_at'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'property_id' => $this->translateAttribute('property'),
            'type' => $this->translateAttribute('meter_type'),
            ...$this->translatedAttributes([
                'name',
                'identifier',
                'unit',
                'status',
                'installed_at',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'property_id',
            'name',
            'identifier',
            'type',
            'unit',
            'status',
            'installed_at',
        ]);

        $this->emptyStringsToNull([
            'unit',
            'installed_at',
        ]);

        $type = $this->input('type');

        if ($type instanceof MeterType) {
            $this->merge([
                'type' => $type->value,
            ]);
        }

        $status = $this->input('status');

        if ($status instanceof MeterStatus) {
            $this->merge([
                'status' => $status->value,
            ]);
        }
    }
}
