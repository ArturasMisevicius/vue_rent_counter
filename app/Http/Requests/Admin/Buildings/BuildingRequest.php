<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Buildings;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

class BuildingRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        $user = $this->user();

        return ($user?->isSuperadmin() || $user?->isAdmin() || $user?->isManager()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country_code' => ['nullable', 'string', 'size:2'],
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
            'address_line_1.required' => ['required', 'address_line_1'],
            'address_line_1.max' => ['max.string', 'address_line_1', ['max' => 255]],
            'address_line_2.max' => ['max.string', 'address_line_2', ['max' => 255]],
            'city.max' => ['max.string', 'city', ['max' => 255]],
            'postal_code.max' => ['max.string', 'postal_code', ['max' => 20]],
            'country_code.size' => ['size.string', 'country_code', ['size' => 2]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'name',
            'address_line_1',
            'address_line_2',
            'city',
            'postal_code',
            'country_code',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'name',
            'address_line_1',
            'address_line_2',
            'city',
            'postal_code',
            'country_code',
        ]);

        $this->emptyStringsToNull([
            'address_line_2',
            'city',
            'postal_code',
            'country_code',
        ]);

        $countryCode = $this->input('country_code');

        if (is_string($countryCode)) {
            $this->merge([
                'country_code' => strtoupper($countryCode),
            ]);
        }
    }
}
