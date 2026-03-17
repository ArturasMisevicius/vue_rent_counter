<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompleteOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('organizations', 'slug'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $slug = (string) ($this->input('slug') ?: $this->input('name'));

        $this->merge([
            'slug' => Str::slug($slug),
        ]);
    }
}
