<?php

namespace App\Http\Requests\Superadmin\Subscriptions;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function ruleset(): array
    {
        return [
            'organization_id' => ['required', 'integer', Rule::exists(Organization::class, 'id')],
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
            'status' => ['required', Rule::enum(SubscriptionStatus::class)],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'is_trial' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::ruleset();
    }
}
