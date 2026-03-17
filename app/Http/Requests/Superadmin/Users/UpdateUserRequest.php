<?php

namespace App\Http\Requests\Superadmin\Users;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function ruleset(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::enum(UserRole::class)],
            'organization_id' => ['nullable', 'integer', Rule::exists(Organization::class, 'id')],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::ruleset($this->route('record'));
    }
}
