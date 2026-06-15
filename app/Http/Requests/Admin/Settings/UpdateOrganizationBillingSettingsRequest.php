<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use App\Enums\BillingFrequency;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationBillingSettingsRequest extends FormRequest
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
            'auto_generation_enabled' => ['required', 'boolean'],
            'billing_frequency' => ['required', Rule::enum(BillingFrequency::class)],
            'invoice_generation_day' => ['required', 'integer', 'min:1', 'max:28'],
            'reading_deadline_day' => ['required', 'integer', 'min:1', 'max:28'],
            'payment_due_days' => ['required', 'integer', 'min:0', 'max:90'],
            'send_created_notification' => ['required', 'boolean'],
            'send_reminders' => ['required', 'boolean'],
            'reminder_days_before_deadline' => ['nullable', 'array'],
            'reminder_days_before_deadline.*' => ['integer', 'min:0', 'max:31'],
            'timezone' => ['required', 'timezone'],
            'default_currency' => ['required', 'string', 'size:3'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'auto_generation_enabled',
            'billing_frequency',
            'invoice_generation_day',
            'reading_deadline_day',
            'payment_due_days',
            'send_created_notification',
            'send_reminders',
            'reminder_days_before_deadline',
            'timezone',
            'default_currency',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'billing_frequency',
            'timezone',
            'default_currency',
        ]);

        $this->castBooleans([
            'auto_generation_enabled',
            'send_created_notification',
            'send_reminders',
        ]);
    }
}
