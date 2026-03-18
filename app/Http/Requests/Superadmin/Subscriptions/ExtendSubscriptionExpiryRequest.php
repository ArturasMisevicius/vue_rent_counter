<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\Subscriptions;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

class ExtendSubscriptionExpiryRequest extends FormRequest
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
            'expires_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'expires_at.required' => ['required', 'expires_at'],
            'expires_at.date' => ['date', 'expires_at'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'expires_at' => $this->translateAttribute('expires_at'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'expires_at',
        ]);
    }
}
