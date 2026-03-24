<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenants;

use App\Enums\UserStatus;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
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
            'locale' => ['required', Rule::in(array_keys(config('tenanto.locales', [])))],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'property_id' => [
                'nullable',
                'integer',
                Rule::exists('properties', 'id')->where(
                    fn ($query) => $query->where('organization_id', $tenant?->organization_id),
                ),
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
            'locale.required' => ['required', 'locale'],
            'locale.in' => ['in', 'locale'],
            'status.required' => ['required', 'status'],
            'status.enum' => ['enum', 'status'],
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
                'locale',
                'status',
                'unit_area_sqm',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'name',
            'email',
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
