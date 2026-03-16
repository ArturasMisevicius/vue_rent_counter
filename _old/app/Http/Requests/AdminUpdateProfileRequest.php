<?php

namespace App\Http\Requests;

use App\Support\EuropeanCurrencyOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUpdateProfileRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$userId],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', Rule::in(EuropeanCurrencyOptions::codes())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('users.validation.name.required'),
            'name.string' => __('users.validation.name.string'),
            'name.max' => __('users.validation.name.max'),
            'email.required' => __('users.validation.email.required'),
            'email.email' => __('users.validation.email.email'),
            'email.unique' => __('users.validation.email.unique'),
            'organization_name.string' => __('users.validation.organization_name.string'),
            'organization_name.max' => __('users.validation.organization_name.max'),
            'currency.in' => __('settings.validation.currency.in'),
        ];
    }
}
