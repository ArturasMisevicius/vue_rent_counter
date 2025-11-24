<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalculateGyvatukasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies/controllers
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
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
            'start_date.required' => __('buildings.validation.gyvatukas.start_date.required'),
            'start_date.date' => __('buildings.validation.gyvatukas.start_date.date'),
            'end_date.required' => __('buildings.validation.gyvatukas.end_date.required'),
            'end_date.date' => __('buildings.validation.gyvatukas.end_date.date'),
            'end_date.after' => __('buildings.validation.gyvatukas.end_date.after'),
        ];
    }
}
