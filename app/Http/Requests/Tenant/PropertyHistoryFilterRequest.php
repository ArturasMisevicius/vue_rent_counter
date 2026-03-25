<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyHistoryFilterRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user()?->isTenant() ?? false;
    }

    public function rules(): array
    {
        return [
            'selectedYear' => ['nullable', 'string', 'regex:/^(all|\d{4})$/'],
            'selectedMonth' => ['nullable', 'string', Rule::in([
                'all',
                '1',
                '2',
                '3',
                '4',
                '5',
                '6',
                '7',
                '8',
                '9',
                '10',
                '11',
                '12',
            ])],
        ];
    }

    public function messages(): array
    {
        return $this->translatedMessages([
            'selectedYear.regex' => ['regex', 'property_history_year'],
            'selectedMonth.in' => ['in', 'property_history_month'],
        ]);
    }

    public function attributes(): array
    {
        return [
            'selectedYear' => $this->translateAttribute('property_history_year'),
            'selectedMonth' => $this->translateAttribute('property_history_month'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'selectedYear',
            'selectedMonth',
        ]);

        $yearInput = $this->input('selectedYear');
        $monthInput = $this->input('selectedMonth');

        $year = match (true) {
            $yearInput === null => 'all',
            ! is_string($yearInput) => $yearInput,
            $yearInput === '',
            $yearInput === 'all' => 'all',
            default => $yearInput,
        };

        $month = match (true) {
            $monthInput === null => 'all',
            ! is_string($monthInput) => $monthInput,
            $monthInput === '',
            $monthInput === 'all' => 'all',
            ctype_digit($monthInput) => (string) max(1, min(12, (int) $monthInput)),
            default => $monthInput,
        };

        $this->merge([
            'selectedYear' => $year,
            'selectedMonth' => $month,
        ]);
    }
}
