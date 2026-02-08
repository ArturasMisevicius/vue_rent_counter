<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\EuropeanCurrencyOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $availableLocales = array_keys((array) config('locales.available', [
            'en' => [],
            'lt' => [],
            'ru' => [],
        ]));

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:organizations,slug'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:organizations,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:organizations,domain'],
            'plan' => ['required', Rule::enum(\App\Enums\SubscriptionPlan::class)],
            'max_properties' => ['required', 'integer', 'min:1'],
            'max_users' => ['required', 'integer', 'min:1'],
            'subscription_ends_at' => ['nullable', 'date'],
            'timezone' => ['nullable', 'timezone'],
            'locale' => ['nullable', 'string', Rule::in($availableLocales)],
            'currency' => ['nullable', 'string', 'size:3', Rule::in(EuropeanCurrencyOptions::codes())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $slug = (string) $this->input('slug', '');
        $name = (string) $this->input('name', '');

        if ($slug === '' && $name !== '') {
            $this->merge([
                'slug' => Str::slug($name),
            ]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->is('superadmin/*')) {
            throw new HttpResponseException(response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
