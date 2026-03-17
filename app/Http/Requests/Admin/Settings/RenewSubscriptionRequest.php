<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RenewSubscriptionRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'plan',
            'duration',
        ]);
    }
}
