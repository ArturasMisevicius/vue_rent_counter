<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
        return [
            'app_name' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'in:Europe/Vilnius,UTC'],
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
            'app_name.string' => __('settings.validation.app_name.string'),
            'app_name.max' => __('settings.validation.app_name.max'),
            'timezone.string' => __('settings.validation.timezone.string'),
            'timezone.in' => __('settings.validation.timezone.in'),
        ];
    }
}
