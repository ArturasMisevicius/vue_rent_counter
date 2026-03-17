<?php

namespace App\Filament\Actions\Superadmin\Notifications;

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use App\Models\PlatformNotification;
use Illuminate\Support\Facades\Validator;

class SavePlatformNotificationDraftAction
{
    public function handle(array $attributes): PlatformNotification
    {
        /** @var array{title: string, body: string, severity: PlatformNotificationSeverity} $validated */
        $validated = Validator::make($attributes, [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'severity' => ['required'],
        ])->validate();

        return PlatformNotification::query()->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'severity' => $validated['severity'],
            'status' => PlatformNotificationStatus::DRAFT,
        ]);
    }
}
