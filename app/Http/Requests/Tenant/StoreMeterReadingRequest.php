<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

class StoreMeterReadingRequest extends FormRequest
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
            'meterId' => ['required', 'integer'],
            'readingValue' => ['required', 'numeric', 'gt:0'],
            'readingDate' => ['required', 'date', 'before_or_equal:'.now()->toDateString()],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'meterId.required' => (string) __('tenant.pages.readings.validation.meter_required'),
            'meterId.integer' => (string) __('tenant.pages.readings.validation.meter_invalid'),
            'readingValue.required' => (string) __('tenant.pages.readings.validation.reading_value_required'),
            'readingValue.numeric' => (string) __('tenant.pages.readings.validation.reading_value_numeric'),
            'readingValue.gt' => (string) __('tenant.pages.readings.validation.reading_value_positive'),
            'readingDate.required' => (string) __('tenant.pages.readings.validation.reading_date_required'),
            'readingDate.date' => (string) __('tenant.pages.readings.validation.reading_date_invalid'),
            'readingDate.before_or_equal' => (string) __('tenant.pages.readings.validation.reading_date_not_future', [
                'date' => LocalizedDateFormatter::date(now()->toDateString()),
            ]),
            'notes.max' => (string) __('tenant.pages.readings.validation.notes_too_long', ['max' => 1000]),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'meterId' => $this->translateAttribute('meter'),
            'readingValue' => $this->translateAttribute('reading_value'),
            'readingDate' => $this->translateAttribute('reading_date'),
            'notes' => $this->translateAttribute('notes'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'meterId',
            'readingValue',
            'readingDate',
            'notes',
        ]);

        $this->emptyStringsToNull([
            'notes',
        ]);
    }
}
