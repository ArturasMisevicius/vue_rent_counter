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
            'new_rate_schedule.time_slots' => ['nullable', 'array'],
            'new_rate_schedule.tiers' => ['nullable', 'array'],
        ];
    }
}

