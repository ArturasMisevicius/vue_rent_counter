<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenants;

use App\Enums\PropertyAssignmentStatus;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Models\Property;
use App\Models\User;
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
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email'), 'disposable_email'],
            'phone' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value) || (bool) $this->input('duplicate_override') || $this->organizationId === null) {
                        return;
                    }

                    $exists = User::query()
                        ->select(['id', 'organization_id', 'role', 'phone'])
                        ->forOrganization($this->organizationId)
                        ->tenants()
                        ->where('phone', $value)
                        ->exists();

                    if ($exists) {
                        $fail(__('admin.tenants.messages.duplicate_phone_warning'));
                    }
                },
            ],
            'internal_note' => ['nullable', 'string', 'max:1000'],
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

                    if (
                        $this->input('assignment_status') === PropertyAssignmentStatus::ACTIVE->value
                        && blank($this->input('move_in_date'))
                    ) {
                        $fail(__('admin.tenants.messages.move_in_required_for_active_assignment'));

                        return;
                    }

                    $property = Property::query()
                        ->availableForTenantAssignment($this->organizationId)
                        ->find($value);

                    if ($property === null) {
                        $fail(__('admin.tenants.messages.active_primary_assignment_exists'));
                    }
                },
            ],
            'unit_area_sqm' => ['nullable', 'numeric', 'min:0'],
            'move_in_date' => [
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (
                        filled($this->input('property_id'))
                        && $this->input('assignment_status') === PropertyAssignmentStatus::ACTIVE->value
                        && blank($value)
                    ) {
                        $fail(__('admin.tenants.messages.move_in_required_for_active_assignment'));
                    }
                },
            ],
            'move_out_date' => ['nullable', 'date', 'after_or_equal:move_in_date'],
            'assignment_status' => ['required', Rule::enum(PropertyAssignmentStatus::class)],
            'is_primary' => ['required', 'boolean'],
            'occupants_count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'duplicate_override' => ['required', 'boolean'],
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
            'first_name.max' => ['max.string', 'first_name', ['max' => 255]],
            'last_name.max' => ['max.string', 'last_name', ['max' => 255]],
            'email.required' => ['required', 'email'],
            'email.email' => ['email', 'email'],
            'email.max' => ['max.string', 'email', ['max' => 255]],
            'email.unique' => ['unique', 'email'],
            'email.disposable_email' => ['disposable_email', 'email'],
            'phone.max' => ['max.string', 'phone', ['max' => 255]],
            'internal_note.max' => ['max.string', 'internal_note', ['max' => 1000]],
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
            'move_in_date.date' => ['date', 'move_in_date'],
            'move_out_date.date' => ['date', 'move_out_date'],
            'move_out_date.after_or_equal' => ['after_or_equal', 'move_out_date', [
                'date' => $this->translateAttribute('move_in_date'),
            ]],
            'assignment_status.required' => ['required', 'assignment_status'],
            'assignment_status.enum' => ['enum', 'assignment_status'],
            'is_primary.required' => ['required', 'is_primary'],
            'is_primary.boolean' => ['boolean', 'is_primary'],
            'occupants_count.integer' => ['integer', 'occupants_count'],
            'occupants_count.min' => ['min.numeric', 'occupants_count', ['min' => 1]],
            'occupants_count.max' => ['max.numeric', 'occupants_count', ['max' => 50]],
            'duplicate_override.required' => ['required', 'duplicate_override'],
            'duplicate_override.boolean' => ['boolean', 'duplicate_override'],
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
                'first_name',
                'last_name',
                'name',
                'email',
                'phone',
                'internal_note',
                'locale',
                'create_portal_access',
                'send_invitation_now',
                'invitation_expiration_days',
                'unit_area_sqm',
                'move_in_date',
                'move_out_date',
                'assignment_status',
                'is_primary',
                'occupants_count',
                'duplicate_override',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'first_name',
            'last_name',
            'name',
            'email',
            'phone',
            'internal_note',
            'locale',
            'create_portal_access',
            'send_invitation_now',
            'invitation_expiration_days',
            'property_id',
            'unit_area_sqm',
            'move_in_date',
            'move_out_date',
            'assignment_status',
            'occupants_count',
        ]);

        $createPortalAccess = $this->boolean('create_portal_access', true);
        $sendInvitationNow = $createPortalAccess && $this->boolean('send_invitation_now', true);
        $name = $this->input('name');
        $firstName = $this->input('first_name');
        $lastName = $this->input('last_name');

        if (blank($name)) {
            $name = trim(collect([$firstName, $lastName])->filter(fn (mixed $part): bool => filled($part))->implode(' '));
        }

        $this->emptyStringsToNull([
            'first_name',
            'last_name',
            'internal_note',
            'property_id',
            'unit_area_sqm',
            'move_in_date',
            'move_out_date',
            'occupants_count',
        ]);

        $this->merge([
            'name' => $name,
            'create_portal_access' => $createPortalAccess,
            'send_invitation_now' => $sendInvitationNow,
            'invitation_expiration_days' => $this->integer('invitation_expiration_days', 7),
            'assignment_status' => $this->input('assignment_status') ?: PropertyAssignmentStatus::ACTIVE->value,
            'is_primary' => $this->boolean('is_primary', true),
            'duplicate_override' => $this->boolean('duplicate_override', false),
            'subscription_limit' => true,
        ]);
    }
}
