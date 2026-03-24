<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\Organizations;

use App\Enums\SubscriptionPlan;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
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
            'owner_email' => ['nullable', 'email:rfc', 'max:255', 'disposable_email'],
            'plan' => ['nullable', Rule::enum(SubscriptionPlan::class)],
            'expires_at' => ['nullable', 'date'],
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
            'owner_email.email' => ['email', 'owner_email'],
            'owner_email.max' => ['max.string', 'owner_email', ['max' => 255]],
            'owner_email.disposable_email' => ['disposable_email', 'owner_email'],
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
            ...$this->translatedAttributes([
                'name',
                'owner_email',
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'name',
            'owner_email',
            'plan',
            'expires_at',
        ]);

        $this->emptyStringsToNull([
            'owner_email',
            'plan',
            'expires_at',
        ]);

        $plan = $this->input('plan');

        if ($plan instanceof SubscriptionPlan) {
            $this->merge([
                'plan' => $plan->value,
            ]);
        }
    }
}
