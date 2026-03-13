<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'tenant_id' => ['required', 'integer'],
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
            'name.required' => __('users.validation.name.required'),
            'name.string' => __('users.validation.name.string'),
            'name.max' => __('users.validation.name.max'),
            'email.required' => __('users.validation.email.required'),
            'email.string' => __('users.validation.email.string'),
            'email.email' => __('users.validation.email.email'),
            'email.max' => __('users.validation.email.max'),
            'email.unique' => __('users.validation.email.unique'),
            'password.required' => __('users.validation.password.required'),
            'password.string' => __('users.validation.password.string'),
            'password.min' => __('users.validation.password.min'),
            'password.confirmed' => __('users.validation.password.confirmed'),
            'tenant_id.required' => __('users.validation.tenant_id.required'),
            'tenant_id.integer' => __('users.validation.tenant_id.integer'),
        ];
    }
}
