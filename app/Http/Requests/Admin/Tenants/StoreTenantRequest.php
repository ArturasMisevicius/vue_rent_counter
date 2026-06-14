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
            'create_portal_access' => ['required', 'boolean'],
            'send_invitation_now' => [
                'required',
                'boolean',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ((bool) $value && ! (bool) $this->input('create_portal_access')) {
                        $fail(__('admin.tenants.messages.portal_access_required_for_invitation'));
                    }
                },
            ],
            'invitation_expiration_days' => ['required', 'integer', 'min:1', 'max:60'],
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
            'create_portal_access.required' => ['required', 'create_portal_access'],
            'create_portal_access.boolean' => ['boolean', 'create_portal_access'],
            'send_invitation_now.required' => ['required', 'send_invitation_now'],
            'send_invitation_now.boolean' => ['boolean', 'send_invitation_now'],
            'invitation_expiration_days.required' => ['required', 'invitation_expiration_days'],
            'invitation_expiration_days.integer' => ['integer', 'invitation_expiration_days'],
            'invitation_expiration_days.min' => ['min.numeric', 'invitation_expiration_days', ['min' => 1]],
            'invitation_expiration_days.max' => ['max.numeric', 'invitation_expiration_days', ['max' => 60]],
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
                'create_portal_access',
                'send_invitation_now',
                'invitation_expiration_days',
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
            'create_portal_access',
            'send_invitation_now',
            'invitation_expiration_days',
            'property_id',
            'unit_area_sqm',
        ]);

        $createPortalAccess = $this->boolean('create_portal_access', true);
        $sendInvitationNow = $createPortalAccess && $this->boolean('send_invitation_now', true);

        $this->emptyStringsToNull([
            'property_id',
            'unit_area_sqm',
        ]);

        $this->merge([
            'create_portal_access' => $createPortalAccess,
            'send_invitation_now' => $sendInvitationNow,
            'invitation_expiration_days' => $this->integer('invitation_expiration_days', 7),
            'subscription_limit' => true,
        ]);
    }
}
