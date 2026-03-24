<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MeterReadings;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

class RejectMeterReadingRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'reason.required' => ['required', 'reason'],
            'reason.max' => ['max.string', 'reason', ['max' => 1000]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'reason',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'reason',
        ]);
    }
}
