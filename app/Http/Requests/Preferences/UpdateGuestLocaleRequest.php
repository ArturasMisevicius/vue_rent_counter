<?php

namespace App\Http\Requests\Preferences;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuestLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => [
                'required',
                'string',
                Rule::in(array_keys(config('app.supported_locales', []))),
            ],
        ];
    }

    public function locale(): string
    {
        /** @var string $locale */
        $locale = $this->validated('locale');

        return $locale;
    }
}
