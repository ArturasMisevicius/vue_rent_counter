<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MeterReadings;

use App\Enums\MeterReadingSubmissionMethod;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeterReadingRequest extends FormRequest
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
            'reading_value' => ['required', 'numeric', 'min:0'],
            'reading_date' => ['required', 'date'],
            'submission_method' => ['required', Rule::enum(MeterReadingSubmissionMethod::class)],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'reading_value.required' => ['required', 'reading_value'],
            'reading_value.numeric' => ['numeric', 'reading_value'],
            'reading_value.min' => ['min.numeric', 'reading_value', ['min' => 0]],
            'reading_date.required' => ['required', 'reading_date'],
            'reading_date.date' => ['date', 'reading_date'],
            'submission_method.required' => ['required', 'submission_method'],
            'submission_method.enum' => ['enum', 'submission_method'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'reading_value',
            'reading_date',
            'submission_method',
            'notes',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'reading_value',
            'reading_date',
            'submission_method',
            'notes',
        ]);

        $this->emptyStringsToNull([
            'notes',
        ]);

        $submissionMethod = $this->input('submission_method');

        if ($submissionMethod instanceof MeterReadingSubmissionMethod) {
            $this->merge([
                'submission_method' => $submissionMethod->value,
            ]);
        }
    }
}
