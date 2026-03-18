<?php

namespace App\Filament\Actions\Superadmin\Notifications;

use App\Enums\PlatformNotificationStatus;
use App\Enums\UserStatus;
use App\Events\PlatformNotificationSent;
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
        $users = $recipients ?? $this->resolveRecipients($notification);
        $sentAt = now();
        $broadcastPayloads = [];

        DB::transaction(function () use (&$broadcastPayloads, $notification, $sentAt, $users): void {
            foreach ($users as $user) {
                PlatformNotificationDelivery::query()->create([
                    'platform_notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'channel' => 'database',
                    'delivered_at' => $sentAt,
                ]);
            }

            foreach ($users->groupBy('organization_id') as $organizationId => $organizationUsers) {
                if (! is_numeric($organizationId)) {
                    continue;
                }

                $primaryEmail = (string) ($organizationUsers->first()?->email ?? '');

                $recipient = PlatformNotificationRecipient::query()->updateOrCreate(
                    [
                        'platform_notification_id' => $notification->id,
                        'organization_id' => (int) $organizationId,
                    ],
                    [
                        'email' => $primaryEmail,
                        'delivery_status' => 'sent',
                        'sent_at' => $sentAt,
                        'failure_reason' => null,
                    ],
                );

                $broadcastPayloads[] = [
                    'organization_id' => (int) $organizationId,
                    'notification_id' => $notification->id,
                    'recipient_id' => $recipient->id,
                ];
            }

            PlatformNotification::query()
                ->whereKey($notification->getKey())
                ->update([
                    'status' => PlatformNotificationStatus::SENT,
                    'sent_at' => $sentAt,
                ]);
        });

        foreach ($broadcastPayloads as $payload) {
            event(new PlatformNotificationSent(
                $payload['organization_id'],
                $payload['notification_id'],
                $payload['recipient_id'],
            ));
        }

        return $notification->fresh(['deliveries', 'recipients']);
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveRecipients(PlatformNotification $notification): Collection
    {
        $organizationIds = $notification->recipients()
            ->select(['organization_id'])
            ->pluck('organization_id')
            ->filter()
            ->unique()
            ->values();

        $query = User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'locale', 'password', 'remember_token'])
            ->where('status', UserStatus::ACTIVE);

        if ($organizationIds->isNotEmpty()) {
            $query->whereIn('organization_id', $organizationIds->all());
        }

        return $query->get();
    }
}
