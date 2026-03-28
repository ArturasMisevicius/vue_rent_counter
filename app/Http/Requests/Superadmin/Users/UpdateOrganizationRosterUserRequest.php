<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\Users;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRosterUserRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private ?User $record = null;

    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    public function forRecord(User $record): self
    {
        $request = clone $this;
        $request->record = $record;

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->record?->getKey()),
                'disposable_email',
            ],
            'role' => ['required', Rule::enum(UserRole::class), Rule::notIn([UserRole::SUPERADMIN->value])],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'locale' => ['required', Rule::in(array_keys(config('tenanto.locales', [])))],
            'password' => ['nullable', 'string', 'min:8'],
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
            'role.required' => ['required', 'role'],
            'role.enum' => ['enum', 'role'],
            'role.not_in' => ['in', 'role'],
            'status.required' => ['required', 'status'],
            'status.enum' => ['enum', 'status'],
            'locale.required' => ['required', 'locale'],
            'locale.in' => ['in', 'locale'],
            'password.min' => ['min.string', 'password', ['min' => 8]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'name',
            'email',
            'role',
            'status',
            'locale',
            'password',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'name',
            'email',
            'role',
            'status',
            'locale',
            'password',
        ]);

        $this->emptyStringsToNull([
            'password',
        ]);

        $role = $this->input('role');
        $status = $this->input('status');

        if ($role instanceof UserRole) {
            $this->merge([
                'role' => $role->value,
            ]);
        }

        if ($status instanceof UserStatus) {
            $this->merge([
                'status' => $status->value,
            ]);
        }
    }
}
