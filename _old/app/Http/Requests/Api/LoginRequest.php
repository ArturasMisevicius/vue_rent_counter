<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * API Login Request
 *
 * Validates API authentication requests.
 */
class LoginRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'token_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => __('validation.custom_requests.api_login.email.required'),
            'email.email' => __('validation.custom_requests.api_login.email.email'),
            'password.required' => __('validation.custom_requests.api_login.password.required'),
            'password.min' => __('validation.custom_requests.api_login.password.min'),
        ];
    }
}
