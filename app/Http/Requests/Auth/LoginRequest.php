<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'email.required' => ['required', 'email'],
            'email.email' => ['email', 'email'],
            'password.required' => ['required', 'password'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'email',
            'password',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'email',
        ]);
    }

    /**
     * @return array{email: string, password: string}
     */
    public function credentials(): array
    {
        /** @var array{email: string, password: string} $credentials */
        $credentials = $this->safe()->only([
            'email',
            'password',
        ]);

        return $credentials;
    }
}
