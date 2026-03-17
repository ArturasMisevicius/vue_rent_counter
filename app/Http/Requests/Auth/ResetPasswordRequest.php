<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    use InteractsWithValidationPayload;

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'token.required' => ['required', 'token'],
            'email.required' => ['required', 'email'],
            'email.email' => ['email', 'email'],
            'password.required' => ['required', 'password'],
            'password.confirmed' => ['confirmed', 'password'],
            'password.min' => ['min.string', 'password', ['min' => 8]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'token',
            'email',
            'password',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'token',
            'email',
        ]);
    }
}
