<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\Subscriptions;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationSubscriptionRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
            'status' => ['required', Rule::enum(SubscriptionStatus::class)],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'plan.required' => ['required', 'subscription_plan'],
            'plan.enum' => ['enum', 'subscription_plan'],
            'status.required' => ['required', 'subscription_status'],
            'status.enum' => ['enum', 'subscription_status'],
            'starts_at.required' => ['required', 'starts_at'],
            'starts_at.date' => ['date', 'starts_at'],
            'expires_at.required' => ['required', 'expires_at'],
            'expires_at.date' => ['date', 'expires_at'],
            'expires_at.after_or_equal' => ['after_or_equal', 'expires_at', ['date' => $this->translateAttribute('starts_at')]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'plan' => $this->translateAttribute('subscription_plan'),
            'status' => $this->translateAttribute('subscription_status'),
            'starts_at' => $this->translateAttribute('starts_at'),
            'expires_at' => $this->translateAttribute('expires_at'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'plan',
            'status',
            'starts_at',
            'expires_at',
        ]);

        $plan = $this->input('plan');
        $status = $this->input('status');

        if ($plan instanceof SubscriptionPlan) {
            $this->merge([
                'plan' => $plan->value,
            ]);
        }

        if ($status instanceof SubscriptionStatus) {
            $this->merge([
                'status' => $status->value,
            ]);
        }
    }
}
