<?php

namespace App\Actions\Superadmin\Notifications;

use App\Enums\PlatformNotificationStatus;
use App\Http\Requests\Superadmin\PlatformNotifications\StorePlatformNotificationRequest;
use App\Models\PlatformNotification;
use Illuminate\Support\Facades\Validator;

class SavePlatformNotificationDraftAction
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __invoke(array $attributes, ?PlatformNotification $platformNotification = null): PlatformNotification
    {
        $data = Validator::make($attributes, StorePlatformNotificationRequest::ruleset())->validate();

        $payload = [
            ...$data,
            'author_id' => auth()->id(),
            'status' => PlatformNotificationStatus::DRAFT,
            'sent_at' => null,
        ];

        if ($platformNotification instanceof PlatformNotification) {
            $platformNotification->fill($payload)->save();

            return $platformNotification->refresh()->loadCount('deliveries');
        }

        return PlatformNotification::query()
            ->create($payload)
            ->loadCount('deliveries');
    }
}
