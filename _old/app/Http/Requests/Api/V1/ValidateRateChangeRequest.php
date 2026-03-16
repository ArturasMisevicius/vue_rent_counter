<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for rate change validation API (v1).
 */
class ValidateRateChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in the controller to ensure consistent 403 JSON
        // responses without throwing AuthorizationException during tests.
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'new_rate_schedule' => ['required', 'array', 'min:1'],
            'new_rate_schedule.rate_per_unit' => ['nullable', 'numeric', 'min:0'],
            'new_rate_schedule.monthly_rate' => ['nullable', 'numeric', 'min:0'],
            'new_rate_schedule.effective_from' => ['nullable', 'date'],
            'new_rate_schedule.zone_rates' => ['nullable', 'array'],
            'new_rate_schedule.zone_rates.*' => ['nullable', 'numeric', 'min:0'],
            'new_rate_schedule.time_slots' => ['nullable', 'array'],
            'new_rate_schedule.time_slots.*.zone' => ['nullable', 'string', 'max:50'],
            'new_rate_schedule.time_slots.*.day_type' => ['nullable', 'string', 'in:weekday,weekend,all'],
            'new_rate_schedule.time_slots.*.start_hour' => ['required_with:new_rate_schedule.time_slots', 'integer', 'min:0', 'max:23'],
            'new_rate_schedule.time_slots.*.end_hour' => ['required_with:new_rate_schedule.time_slots', 'integer', 'min:0', 'max:23'],
            'new_rate_schedule.time_slots.*.rate' => ['required_with:new_rate_schedule.time_slots', 'numeric', 'min:0'],
            'new_rate_schedule.time_windows' => ['nullable', 'array'],
            'new_rate_schedule.time_windows.*.zone' => ['required_with:new_rate_schedule.time_windows', 'string', 'max:50'],
            'new_rate_schedule.time_windows.*.start' => ['required_with:new_rate_schedule.time_windows', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            'new_rate_schedule.time_windows.*.end' => ['required_with:new_rate_schedule.time_windows', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            'new_rate_schedule.time_windows.*.rate' => ['required_with:new_rate_schedule.time_windows', 'numeric', 'min:0'],
            'new_rate_schedule.time_windows.*.day_types' => ['nullable', 'array'],
            'new_rate_schedule.time_windows.*.day_types.*' => ['string', 'in:weekday,weekend,all'],
            'new_rate_schedule.time_windows.*.months' => ['nullable', 'array'],
            'new_rate_schedule.time_windows.*.months.*' => ['integer', 'min:1', 'max:12'],
            'new_rate_schedule.tiers' => ['nullable', 'array'],
            'new_rate_schedule.localization' => ['nullable', 'array'],
            'new_rate_schedule.localization.locale' => ['nullable', 'string', 'max:20'],
            'new_rate_schedule.localization.minimum_charge' => ['nullable', 'numeric', 'min:0'],
            'new_rate_schedule.localization.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'new_rate_schedule.localization.money_precision' => ['nullable', 'integer', 'min:0', 'max:6'],
            'new_rate_schedule.localization.rounding_mode' => ['nullable', 'string', 'in:half_up,half_down,bankers,up,down'],
            'new_rate_schedule.localization.fixed_charges' => ['nullable', 'array'],
            'new_rate_schedule.localization.fixed_charges.*.name' => ['required_with:new_rate_schedule.localization.fixed_charges', 'string', 'max:100'],
            'new_rate_schedule.localization.fixed_charges.*.amount' => ['required_with:new_rate_schedule.localization.fixed_charges', 'numeric', 'min:0'],
            'new_rate_schedule.localization.surcharges' => ['nullable', 'array'],
            'new_rate_schedule.localization.surcharges.*.name' => ['required_with:new_rate_schedule.localization.surcharges', 'string', 'max:100'],
            'new_rate_schedule.localization.surcharges.*.percentage' => ['required_with:new_rate_schedule.localization.surcharges', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
