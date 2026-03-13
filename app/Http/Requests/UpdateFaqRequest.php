<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for updating FAQ entries.
 *
 * Security validations:
 * - Same rules as StoreFaqRequest
 * - Additional authorization check for specific FAQ
 *
 * @see \App\Models\Faq
 * @see \App\Http\Requests\StoreFaqRequest
 */
final class UpdateFaqRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $faq = $this->route('faq');
        return $this->user()->can('update', $faq);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => [
                'required',
                'string',
                'min:10',
                'max:255',
                'regex:/^[a-zA-Z0-9\s\?\.\,\!\-\(\)]+$/u',
            ],
            'answer' => [
                'required',
                'string',
                'min:10',
                'max:10000',
            ],
            'category' => [
                'nullable',
                'string',
                'max:120',
                'regex:/^[a-zA-Z0-9\s\-\_]+$/u',
            ],
            'display_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],
            'is_published' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'question' => __('faq.labels.question'),
            'answer' => __('faq.labels.answer'),
            'category' => __('faq.labels.category'),
            'display_order' => __('faq.labels.display_order'),
            'is_published' => __('faq.labels.published'),
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
            'question.regex' => __('faq.validation.question_format'),
            'category.regex' => __('faq.validation.category_format'),
            'answer.min' => __('faq.validation.answer_too_short'),
            'answer.max' => __('faq.validation.answer_too_long'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace
        $this->merge([
            'question' => trim($this->input('question', '')),
            'category' => trim($this->input('category', '')),
            'answer' => trim($this->input('answer', '')),
        ]);
    }
}
