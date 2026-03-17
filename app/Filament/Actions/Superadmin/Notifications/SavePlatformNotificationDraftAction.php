<?php

namespace App\Filament\Actions\Superadmin\Notifications;

use App\Enums\PlatformNotificationStatus;
use App\Http\Requests\Superadmin\Notifications\SendPlatformNotificationRequest;
use App\Models\PlatformNotification;

class SavePlatformNotificationDraftAction
{
    public function handle(array $attributes): PlatformNotification
    {
        /** @var SendPlatformNotificationRequest $request */
        $request = new SendPlatformNotificationRequest;
        $validated = $request
            ->requireSeverity()
            ->validatePayload($attributes);

        return PlatformNotification::query()->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'severity' => $validated['severity'],
            'status' => PlatformNotificationStatus::DRAFT,
        ]);
    }
}
