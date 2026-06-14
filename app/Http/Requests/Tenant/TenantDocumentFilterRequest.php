<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\TenantDocumentType;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantDocumentFilterRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user()?->isTenant() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'selectedCategory' => [
                'required',
                'string',
                Rule::in(['all', ...TenantDocumentType::values()]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'selectedCategory',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings(['selectedCategory']);

        $this->merge([
            'selectedCategory' => $this->input('selectedCategory') ?: 'all',
        ]);
    }
}
