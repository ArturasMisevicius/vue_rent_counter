<?php

namespace App\Http\Requests\Superadmin\PlatformNotifications;

use App\Enums\PlatformNotificationSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlatformNotificationRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'severity' => ['required', Rule::enum(PlatformNotificationSeverity::class)],
            'target_scope' => ['required', 'string', Rule::in(array_keys(config('tenanto.notifications.target_scopes', [])))],
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
