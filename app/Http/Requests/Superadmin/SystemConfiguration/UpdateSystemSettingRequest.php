<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\SystemConfiguration;

use App\Filament\Support\Superadmin\SystemConfiguration\SystemSettingCatalog;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingRequest extends FormRequest
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
            'key' => ['nullable', 'string'],
            'value' => app(SystemSettingCatalog::class)->validationRulesFor((string) $this->input('key')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'value.required' => ['required', 'value'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'value',
        ]);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('value'))) {
            $this->trimStrings([
                'value',
            ]);
        }
    }
}
