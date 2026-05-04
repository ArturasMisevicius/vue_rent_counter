<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenants;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Models\Property;
use App\Rules\WithinTenantLimit;
use App\Services\SubscriptionChecker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private ?int $organizationId = null;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isAdminLike() ?? false;
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
        $user = $this->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email'), 'disposable_email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'locale' => ['required', Rule::in(array_keys(config('tenanto.locales', [])))],
            'property_id' => [
                'nullable',
                'integer',
                Rule::exists('properties', 'id')->where(
                    fn ($query) => $query->where('organization_id', $this->organizationId),
                ),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value) || $this->organizationId === null) {
                        return;
                    }

                    $property = Property::query()
                        ->availableForTenantAssignment($this->organizationId)
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

        if ($user?->isSuperadmin()) {
            return $rules;
        }

        return [
            ...$rules,
            'subscription_limit' => [new WithinTenantLimit(app(SubscriptionChecker::class))],
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
            'subscription_limit' => $this->translateAttribute('tenant_id'),
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

        $this->merge([
            'subscription_limit' => true,
        ]);
    }
}
