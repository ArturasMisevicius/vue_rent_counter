<?php

namespace App\Http\Requests\Superadmin\Organizations;

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::ruleset();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function ruleset(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('organizations', 'slug')],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
            'duration' => ['required', Rule::enum(SubscriptionDuration::class)],
        ];
    }
}
