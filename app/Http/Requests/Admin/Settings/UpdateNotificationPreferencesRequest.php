<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
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
            'invoice_reminders' => ['required', 'boolean'],
            'reading_deadline_alerts' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'invoice_reminders.required' => ['required', 'invoice_reminders'],
            'invoice_reminders.boolean' => ['boolean', 'invoice_reminders'],
            'reading_deadline_alerts.required' => ['required', 'reading_deadline_alerts'],
            'reading_deadline_alerts.boolean' => ['boolean', 'reading_deadline_alerts'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->translatedAttributes([
            'invoice_reminders',
            'reading_deadline_alerts',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->castBooleans([
            'invoice_reminders',
            'reading_deadline_alerts',
        ]);
    }
}
