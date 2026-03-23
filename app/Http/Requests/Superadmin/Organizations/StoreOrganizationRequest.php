<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\Organizations;

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email:rfc', 'max:255', 'disposable_email'],
            'owner_name' => ['required', 'string', 'max:255'],
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
            'duration' => ['required', Rule::enum(SubscriptionDuration::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'name.required' => ['required', 'name'],
            'name.max' => ['max.string', 'name', ['max' => 255]],
            'owner_email.required' => ['required', 'owner_email'],
            'owner_email.email' => ['email', 'owner_email'],
            'owner_email.max' => ['max.string', 'owner_email', ['max' => 255]],
            'owner_email.disposable_email' => ['disposable_email', 'owner_email'],
            'owner_name.required' => ['required', 'owner_name'],
            'owner_name.max' => ['max.string', 'owner_name', ['max' => 255]],
            'plan.required' => ['required', 'subscription_plan'],
            'plan.enum' => ['enum', 'subscription_plan'],
            'duration.required' => ['required', 'subscription_duration'],
            'duration.enum' => ['enum', 'subscription_duration'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'plan' => $this->translateAttribute('subscription_plan'),
            'duration' => $this->translateAttribute('subscription_duration'),
            ...$this->translatedAttributes([
                'name',
                'owner_email',
                'owner_name',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'name',
            'owner_email',
            'owner_name',
            'plan',
            'duration',
        ]);

        $plan = $this->input('plan');

        if ($plan instanceof SubscriptionPlan) {
            $this->merge([
                'plan' => $plan->value,
            ]);
        }

        $duration = $this->input('duration');

        if ($duration instanceof SubscriptionDuration) {
            $this->merge([
                'duration' => $duration->value,
            ]);
        }
    }
}
