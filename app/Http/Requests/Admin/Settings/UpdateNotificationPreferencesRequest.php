<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use App\Services\NotificationPreferenceService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
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
            NotificationPreferenceService::NEW_INVOICE_GENERATED => ['required', 'boolean'],
            NotificationPreferenceService::INVOICE_OVERDUE => ['required', 'boolean'],
            NotificationPreferenceService::TENANT_SUBMITS_READING => ['required', 'boolean'],
            NotificationPreferenceService::SUBSCRIPTION_EXPIRING => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'new_invoice_generated.required' => ['required', 'new_invoice_generated'],
            'new_invoice_generated.boolean' => ['boolean', 'new_invoice_generated'],
            'invoice_overdue.required' => ['required', 'invoice_overdue'],
            'invoice_overdue.boolean' => ['boolean', 'invoice_overdue'],
            'tenant_submits_reading.required' => ['required', 'tenant_submits_reading'],
            'tenant_submits_reading.boolean' => ['boolean', 'tenant_submits_reading'],
            'subscription_expiring.required' => ['required', 'subscription_expiring'],
            'subscription_expiring.boolean' => ['boolean', 'subscription_expiring'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'new_invoice_generated',
            'invoice_overdue',
            'tenant_submits_reading',
            'subscription_expiring',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->castBooleans([
            'new_invoice_generated',
            'invoice_overdue',
            'tenant_submits_reading',
            'subscription_expiring',
        ]);
    }
}
