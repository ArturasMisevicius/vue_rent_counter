<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateRateChangeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'rate_schedule' => 'required|array',
            'rate_schedule.rate_per_unit' => 'nullable|numeric|min:0|max:999999.99',
            'rate_schedule.monthly_rate' => 'nullable|numeric|min:0|max:999999.99',
            'rate_schedule.base_rate' => 'nullable|numeric|min:0|max:999999.99',
            'rate_schedule.effective_from' => 'nullable|date|after_or_equal:today',
            'rate_schedule.effective_until' => 'nullable|date|after:rate_schedule.effective_from',
            'rate_schedule.time_slots' => 'nullable|array|max:50',
            'rate_schedule.time_slots.*.start_hour' => 'required_with:rate_schedule.time_slots|integer|min:0|max:23',
            'rate_schedule.time_slots.*.end_hour' => 'required_with:rate_schedule.time_slots|integer|min:0|max:23',
            'rate_schedule.time_slots.*.rate' => 'required_with:rate_schedule.time_slots|numeric|min:0|max:999999.99',
            'rate_schedule.time_slots.*.day_type' => 'nullable|string|in:weekday,weekend',
            'rate_schedule.tiers' => 'nullable|array|max:50',
            'rate_schedule.tiers.*.limit' => 'required_with:rate_schedule.tiers|numeric|min:0|max:999999',
            'rate_schedule.tiers.*.rate' => 'required_with:rate_schedule.tiers|numeric|min:0|max:999999.99',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rate_schedule.required' => __('validation.rate_schedule_required'),
            'rate_schedule.*.numeric' => __('validation.rate_must_be_numeric'),
            'rate_schedule.*.min' => __('validation.rate_must_be_positive'),
            'rate_schedule.*.max' => __('validation.rate_exceeds_maximum'),
            'rate_schedule.time_slots.max' => __('validation.too_many_time_slots'),
            'rate_schedule.tiers.max' => __('validation.too_many_tiers'),
        ];
    }
}