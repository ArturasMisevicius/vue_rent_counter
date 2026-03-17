<?php

namespace App\Http\Requests\Preferences;

use App\Support\Geography\BalticReferenceCatalog;
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
                Rule::in(BalticReferenceCatalog::supportedLocaleCodes()),
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
