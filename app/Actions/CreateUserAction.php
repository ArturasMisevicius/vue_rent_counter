<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Create User Action
 * 
 * Single responsibility: Create a new user with validation.
 * Used by AccountManagementService for both admin and tenant creation.
 * 
 * @package App\Actions
 */
final class CreateUserAction
{
    /**
     * Execute the action to create a user.
     *
     * @param array $data User data
     * @return User The created user
     * @throws ValidationException If validation fails
     */
    public function execute(array $data): User
    {
        // Validate input
        $validated = $this->validate($data);

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Create user
        return User::create($validated);
    }

    /**
     * Validate user data.
     *
     * @param array $data
     * @return array Validated data
     * @throws ValidationException
     */
    private function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'sometimes|required|string|min:8',
            'role' => 'required|in:' . implode(',', array_column(UserRole::cases(), 'value')),
            'tenant_id' => 'required|integer',
            'property_id' => 'nullable|exists:properties,id',
            'parent_user_id' => 'nullable|exists:users,id',
            'organization_name' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
