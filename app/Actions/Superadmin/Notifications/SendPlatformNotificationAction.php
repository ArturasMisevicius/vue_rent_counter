<?php

namespace App\Actions\Superadmin\Notifications;

use App\Enums\PlatformNotificationStatus;
use App\Enums\UserStatus;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationDelivery;
use App\Models\PlatformNotificationRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SendPlatformNotificationAction
{
    /**
     * @param  Collection<int, User>|null  $recipients
     */
    public function handle(PlatformNotification $notification, ?Collection $recipients = null): PlatformNotification
    {
        $users = $recipients ?? User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'locale', 'password', 'remember_token'])
            ->where('status', UserStatus::ACTIVE)
            ->get();

        DB::transaction(function () use ($notification, $users): void {
            foreach ($users as $user) {
                PlatformNotificationDelivery::query()->create([
                    'platform_notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'channel' => 'database',
                    'delivered_at' => now(),
                ]);

                if ($user->organization_id !== null) {
                    PlatformNotificationRecipient::query()->firstOrCreate([
                        'platform_notification_id' => $notification->id,
                        'organization_id' => $user->organization_id,
                        'email' => $user->email,
                    ], [
                        'delivery_status' => 'sent',
                        'sent_at' => now(),
                    ]);
                }
            }

            PlatformNotification::query()
                ->whereKey($notification->getKey())
                ->update([
                    'status' => PlatformNotificationStatus::SENT,
                    'sent_at' => now(),
                ]);
        });

        return $notification->fresh(['deliveries']);
    }
}
