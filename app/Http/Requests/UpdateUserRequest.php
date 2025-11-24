<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.required' => __('users.validation.tenant_id.required'),
            'tenant_id.integer' => __('users.validation.tenant_id.integer'),
            'email.unique' => __('users.validation.email.unique'),
            'email.required' => __('users.validation.email.required'),
            'email.email' => __('users.validation.email.email'),
            'email.max' => __('users.validation.email.max'),
            'name.required' => __('users.validation.name.required'),
            'name.max' => __('users.validation.name.max'),
            'role.required' => __('users.validation.role.required'),
            'role.enum' => __('users.validation.role.enum'),
            'tenant_id.exists' => __('users.validation.tenant_id.exists'),
        ];
    }
}
