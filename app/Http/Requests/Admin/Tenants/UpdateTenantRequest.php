<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenants;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private ?User $tenant = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isAdminLike() ?? false;
    }

    public function forTenant(User $tenant): self
    {
        $request = clone $this;
        $request->tenant = $tenant;

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenant = $this->tenant;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($tenant?->id),
                'disposable_email',
            ],
            'phone' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', Rule::in(array_keys(config('tenanto.locales', [])))],
            'property_id' => [
                'nullable',
                'integer',
                Rule::exists('properties', 'id')->where(
                    fn ($query) => $query->where('organization_id', $tenant?->organization_id),
                ),
                function (string $attribute, mixed $value, \Closure $fail) use ($tenant): void {
                    if (blank($value) || $tenant === null) {
                        return;
                    }

                    $property = Property::query()
                        ->availableForTenantAssignment($tenant->organization_id, $tenant->id)
                        ->find($value);

                    if ($property === null) {
                        $fail(__('validation.exists', [
                            'attribute' => $this->translateAttribute('property'),
                        ]));
                    }
                },
            ],
            'unit_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'name.required' => ['required', 'name'],
            'name.max' => ['max.string', 'name', ['max' => 255]],
            'email.required' => ['required', 'email'],
            'email.email' => ['email', 'email'],
            'email.max' => ['max.string', 'email', ['max' => 255]],
            'email.unique' => ['unique', 'email'],
            'email.disposable_email' => ['disposable_email', 'email'],
            'phone.max' => ['max.string', 'phone', ['max' => 255]],
            'locale.required' => ['required', 'locale'],
            'locale.in' => ['in', 'locale'],
            'property_id.integer' => ['integer', 'property'],
            'property_id.exists' => ['exists', 'property'],
            'unit_area_sqm.numeric' => ['numeric', 'unit_area_sqm'],
            'unit_area_sqm.min' => ['min.numeric', 'unit_area_sqm', ['min' => 0]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'property_id' => $this->translateAttribute('property'),
            ...$this->translatedAttributes([
                'name',
                'email',
                'phone',
                'locale',
                'unit_area_sqm',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'name',
            'email',
            'phone',
            'locale',
            'property_id',
            'unit_area_sqm',
        ]);

        $this->emptyStringsToNull([
            'property_id',
            'unit_area_sqm',
        ]);
    }
}
