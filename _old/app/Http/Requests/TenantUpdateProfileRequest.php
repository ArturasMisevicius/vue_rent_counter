<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Tenant Profile Update Form Request
 * 
 * Validates tenant profile update requests including name, email, and password changes.
 * Implements Laravel 12's current_password rule for secure password verification.
 * 
 * @package App\Http\Requests
 * 
 * Validation Rules:
 * - name: Required, string, max 255 characters
 * - email: Required, valid email format, unique (excluding current user)
 * - current_password: Required when changing password, must match user's current password
 * - password: Optional, min 8 characters, must be confirmed
 * 
 * Security Features:
 * - Current password verification using Laravel's built-in rule
 * - Email uniqueness check (excluding current user)
 * - Password confirmation requirement
 * - Localized error messages
 * 
 * Requirements Addressed:
 * - 16.2: Tenant can update their name and email
 * - 16.3: Tenant can change their password securely
 * 
 * @see \App\Http\Controllers\Tenant\ProfileController::update()
 */
class TenantUpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Authorization is handled by route middleware (auth, role:tenant).
     * This method always returns true as the request is already authorized
     * by the time it reaches this point.
     * 
     * @return bool Always returns true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Validation Logic:
     * 1. Name: Always required, must be a string, max 255 characters
     * 2. Email: Always required, must be valid email, unique (excluding current user)
     * 3. Current Password: Required only when password field is present
     *    - Uses Laravel 12's current_password rule for verification
     *    - Automatically checks against authenticated user's password
     * 4. Password: Optional, but if provided:
     *    - Must be at least 8 characters
     *    - Must be confirmed (password_confirmation field must match)
     * 
     * @return array<string, array<int, string>> Validation rules array
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $userId],
            'current_password' => ['nullable', 'required_with:password', 'string', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     * 
     * All error messages are localized using the users.validation translation keys.
     * This ensures consistent error messaging across the application and supports
     * multiple languages (EN/LT/RU).
     * 
     * Translation Files:
     * - lang/en/users.php
     * - lang/lt/users.php
     * - lang/ru/users.php
     * 
     * @return array<string, string> Custom validation messages
     */
    public function messages(): array
    {
        return [
            'name.required' => __('users.validation.name.required'),
            'name.string' => __('users.validation.name.string'),
            'name.max' => __('users.validation.name.max'),
            'email.required' => __('users.validation.email.required'),
            'email.email' => __('users.validation.email.email'),
            'email.unique' => __('users.validation.email.unique'),
            'current_password.required_with' => __('users.validation.current_password.required_with'),
            'current_password.string' => __('users.validation.current_password.string'),
            'password.string' => __('users.validation.password.string'),
            'password.min' => __('users.validation.password.min'),
            'password.confirmed' => __('users.validation.password.confirmed'),
        ];
    }
}
