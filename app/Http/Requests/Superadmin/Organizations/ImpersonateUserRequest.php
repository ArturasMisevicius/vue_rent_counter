<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\Organizations;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImpersonateUserRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'user_id.required' => ['required', 'user'],
            'user_id.integer' => ['integer', 'user'],
            'user_id.exists' => ['exists', 'user'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => $this->translateAttribute('user'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'user_id',
        ]);
    }
}
