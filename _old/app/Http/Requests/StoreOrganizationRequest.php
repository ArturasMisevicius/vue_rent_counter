<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SubscriptionPlan;
use App\Support\EuropeanCurrencyOptions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
     * @return array<string, ValidationRule|array<mixed>|string>
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
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
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
                'message' => __('validation.custom_requests.organizations.invalid_data'),
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
