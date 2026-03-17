<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\Notifications;

use App\Enums\PlatformNotificationSeverity;
use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendPlatformNotificationRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    private bool $severityRequired = false;

    public function authorize(): bool
    {
        return $this->user()?->isSuperadmin() ?? false;
    }

    public function requireSeverity(): self
    {
        $request = clone $this;
        $request->severityRequired = true;

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $severityRules = $this->severityRequired
            ? ['required', Rule::enum(PlatformNotificationSeverity::class)]
            : ['nullable', Rule::enum(PlatformNotificationSeverity::class)];

        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'severity' => $severityRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->translatedMessages([
            'title.required' => ['required', 'title'],
            'title.max' => ['max.string', 'title', ['max' => 255]],
            'body.required' => ['required', 'body'],
            'severity.required' => ['required', 'notification_severity'],
            'severity.enum' => ['enum', 'notification_severity'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => $this->translateAttribute('title'),
            'body' => $this->translateAttribute('body'),
            'severity' => $this->translateAttribute('notification_severity'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'title',
            'body',
            'severity',
        ]);

        $severity = $this->input('severity');

        if ($severity instanceof PlatformNotificationSeverity) {
            $this->merge([
                'severity' => $severity->value,
            ]);
        }

        $this->emptyStringsToNull([
            'severity',
        ]);
    }
}
