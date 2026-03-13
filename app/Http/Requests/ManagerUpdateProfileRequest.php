<?php

namespace App\Http\Requests;

use App\Support\EuropeanCurrencyOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManagerUpdateProfileRequest extends FormRequest
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
            'currency' => ['required', 'string', Rule::in(EuropeanCurrencyOptions::codes())],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
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
            'currency.in' => __('settings.validation.currency.in'),
            'password.string' => __('users.validation.password.string'),
            'password.min' => __('users.validation.password.min'),
            'password.confirmed' => __('users.validation.password.confirmed'),
        ];
    }
}
