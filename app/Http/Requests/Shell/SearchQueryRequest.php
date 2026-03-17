<?php

declare(strict_types=1);

namespace App\Http\Requests\Shell;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;

class SearchQueryRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'query.string' => ['string', 'search_query'],
            'query.max' => ['max.string', 'search_query', ['max' => 120]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'query' => $this->translateAttribute('search_query'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'query',
        ]);
    }
}
