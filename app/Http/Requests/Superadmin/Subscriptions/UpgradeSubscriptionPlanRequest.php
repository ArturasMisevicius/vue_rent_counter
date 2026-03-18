<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\Subscriptions;

use App\Enums\SubscriptionPlan;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpgradeSubscriptionPlanRequest extends FormRequest
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
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'plan' => $this->translateAttribute('subscription_plan'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'plan',
        ]);

        $plan = $this->input('plan');

        if ($plan instanceof SubscriptionPlan) {
            $this->merge([
                'plan' => $plan->value,
            ]);
        }
    }
}
