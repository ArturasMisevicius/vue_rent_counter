<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeterReadingRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    /**
     * @var list<string>
     */
    private array $availableMeterIds = [];

    public function authorize(): bool
    {
        return $this->user()?->isTenant() ?? false;
    }

    public function forAvailableMeters(array $availableMeterIds): self
    {
        $request = clone $this;
        $request->availableMeterIds = array_values(array_map(static fn (string|int $id): string => (string) $id, $availableMeterIds));

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'meterId' => ['required', 'string', Rule::in($this->availableMeterIds)],
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
        return $this->translatedMessages([
            'meterId.required' => ['required', 'meter'],
            'meterId.in' => ['in', 'meter'],
            'readingValue.required' => ['required', 'reading_value'],
            'readingValue.numeric' => ['numeric', 'reading_value'],
            'readingValue.gt' => ['gt.numeric', 'reading_value', ['value' => 0]],
            'readingDate.required' => ['required', 'reading_date'],
            'readingDate.date' => ['date', 'reading_date'],
            'readingDate.before_or_equal' => ['before_or_equal', 'reading_date', ['date' => now()->toDateString()]],
            'notes.max' => ['max.string', 'notes', ['max' => 1000]],
        ]);
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
