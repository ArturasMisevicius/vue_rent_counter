<?php

declare(strict_types=1);

namespace App\Http\Requests\Preferences;

use App\Filament\Support\Preferences\SupportedLocaleOptions;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetLocaleRequest extends FormRequest
{
    use InteractsWithValidationPayload;

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
                Rule::in(app(SupportedLocaleOptions::class)->codes()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'locale.required' => ['required', 'locale'],
            'locale.in' => ['in', 'locale'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'locale',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'locale',
        ]);
    }

    public function locale(): string
    {
        /** @var string $locale */
        $locale = $this->validated('locale');

        return $locale;
    }
}
