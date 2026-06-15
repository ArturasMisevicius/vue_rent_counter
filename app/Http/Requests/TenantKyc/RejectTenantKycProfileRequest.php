<?php

declare(strict_types=1);

namespace App\Http\Requests\TenantKyc;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

class RejectTenantKycProfileRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user()?->isAdminLike() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes(['rejection_reason']);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings(['rejection_reason']);
    }
}
