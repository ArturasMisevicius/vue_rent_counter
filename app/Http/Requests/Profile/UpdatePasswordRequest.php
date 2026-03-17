<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'current_password.required' => ['required', 'current_password'],
            'current_password.current_password' => ['current_password', 'current_password'],
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
            'current_password',
            'password',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'current_password',
        ]);
    }
}
