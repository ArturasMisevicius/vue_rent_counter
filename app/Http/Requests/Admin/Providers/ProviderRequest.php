<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Providers;

use App\Enums\ServiceType;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->isAdminLike() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'service_type' => ['required', Rule::enum(ServiceType::class)],
            'contact_info.phone' => ['nullable', 'string', 'max:255'],
            'contact_info.email' => ['nullable', 'email', 'max:255'],
            'contact_info.website' => ['nullable', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'name.required' => ['required', 'name'],
            'name.max' => ['max.string', 'name', ['max' => 255]],
            'service_type.required' => ['required', 'service_type'],
            'service_type.enum' => ['enum', 'service_type'],
            'contact_info.phone.max' => ['max.string', 'contact_phone', ['max' => 255]],
            'contact_info.email.email' => ['email', 'contact_email'],
            'contact_info.email.max' => ['max.string', 'contact_email', ['max' => 255]],
            'contact_info.website.url' => ['url', 'contact_website'],
            'contact_info.website.max' => ['max.string', 'contact_website', ['max' => 255]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'service_type' => $this->translateAttribute('service_type'),
            'contact_info.phone' => $this->translateAttribute('contact_phone'),
            'contact_info.email' => $this->translateAttribute('contact_email'),
            'contact_info.website' => $this->translateAttribute('contact_website'),
            ...$this->translatedAttributes([
                'name',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'name',
            'service_type',
            'contact_info.phone',
            'contact_info.email',
            'contact_info.website',
        ]);

        $this->emptyStringsToNull([
            'contact_info.phone',
            'contact_info.email',
            'contact_info.website',
        ]);

        $serviceType = $this->input('service_type');

        if ($serviceType instanceof ServiceType) {
            $this->merge([
                'service_type' => $serviceType->value,
            ]);
        }
    }
}
