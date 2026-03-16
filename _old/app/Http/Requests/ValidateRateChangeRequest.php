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
            'rate_schedule.zone_rates' => 'nullable|array',
            'rate_schedule.zone_rates.*' => 'nullable|numeric|min:0|max:999999.99',
            'rate_schedule.time_slots' => 'nullable|array|max:50',
            'rate_schedule.time_slots.*.start_hour' => 'required_with:rate_schedule.time_slots|integer|min:0|max:23',
            'rate_schedule.time_slots.*.end_hour' => 'required_with:rate_schedule.time_slots|integer|min:0|max:23',
            'rate_schedule.time_slots.*.rate' => 'required_with:rate_schedule.time_slots|numeric|min:0|max:999999.99',
            'rate_schedule.time_slots.*.day_type' => 'nullable|string|in:weekday,weekend,all',
            'rate_schedule.time_slots.*.zone' => 'nullable|string|max:50',
            'rate_schedule.time_windows' => 'nullable|array|max:96',
            'rate_schedule.time_windows.*.zone' => 'required_with:rate_schedule.time_windows|string|max:50',
            'rate_schedule.time_windows.*.start' => 'required_with:rate_schedule.time_windows|string|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'rate_schedule.time_windows.*.end' => 'required_with:rate_schedule.time_windows|string|regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/',
            'rate_schedule.time_windows.*.rate' => 'required_with:rate_schedule.time_windows|numeric|min:0|max:999999.99',
            'rate_schedule.time_windows.*.day_types' => 'nullable|array|min:1',
            'rate_schedule.time_windows.*.day_types.*' => 'string|in:weekday,weekend,all',
            'rate_schedule.time_windows.*.months' => 'nullable|array|min:1',
            'rate_schedule.time_windows.*.months.*' => 'integer|min:1|max:12',
            'rate_schedule.tiers' => 'nullable|array|max:50',
            'rate_schedule.tiers.*.limit' => 'required_with:rate_schedule.tiers|numeric|min:0|max:999999',
            'rate_schedule.tiers.*.rate' => 'required_with:rate_schedule.tiers|numeric|min:0|max:999999.99',
            'rate_schedule.localization' => 'nullable|array',
            'rate_schedule.localization.locale' => 'nullable|string|max:20',
            'rate_schedule.localization.minimum_charge' => 'nullable|numeric|min:0|max:999999.99',
            'rate_schedule.localization.tax_rate' => 'nullable|numeric|min:0|max:100',
            'rate_schedule.localization.money_precision' => 'nullable|integer|min:0|max:6',
            'rate_schedule.localization.rounding_mode' => 'nullable|string|in:half_up,half_down,bankers,up,down',
            'rate_schedule.localization.fixed_charges' => 'nullable|array',
            'rate_schedule.localization.fixed_charges.*.name' => 'required_with:rate_schedule.localization.fixed_charges|string|max:100',
            'rate_schedule.localization.fixed_charges.*.amount' => 'required_with:rate_schedule.localization.fixed_charges|numeric|min:0|max:999999.99',
            'rate_schedule.localization.surcharges' => 'nullable|array',
            'rate_schedule.localization.surcharges.*.name' => 'required_with:rate_schedule.localization.surcharges|string|max:100',
            'rate_schedule.localization.surcharges.*.percentage' => 'required_with:rate_schedule.localization.surcharges|numeric|min:0|max:100',
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
