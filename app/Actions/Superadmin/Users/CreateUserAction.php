<?php

namespace App\Actions\Superadmin\Users;

use App\Enums\UserRole;
use App\Http\Requests\Superadmin\Users\StoreUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateUserAction
{
    public function __invoke(array $attributes): User
    {
        $data = Validator::make($attributes, StoreUserRequest::ruleset())
            ->after(fn ($validator) => $this->validateOrganizationAssignment($validator, $attributes))
            ->validate();

        return User::query()->create([
            ...$this->normalize($data),
            'password' => Str::random(40),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if (($data['role'] ?? null) === UserRole::SUPERADMIN->value) {
            $data['organization_id'] = null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function validateOrganizationAssignment(\Illuminate\Validation\Validator $validator, array $attributes): void
    {
        $role = $attributes['role'] ?? null;
        $organizationId = $attributes['organization_id'] ?? null;

        if ($role === UserRole::SUPERADMIN->value && filled($organizationId)) {
            $validator->errors()->add('organization_id', 'Superadmin accounts cannot belong to an organization.');
        }

        if ($role !== UserRole::SUPERADMIN->value && blank($organizationId)) {
            $validator->errors()->add('organization_id', 'An organization is required for non-superadmin users.');
        }
    }
}
