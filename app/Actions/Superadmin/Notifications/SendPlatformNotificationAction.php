<?php

namespace App\Actions\Superadmin\Notifications;

use App\Enums\PlatformNotificationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationDelivery;
use App\Models\User;
use App\Notifications\Superadmin\OrganizationMessageNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SendPlatformNotificationAction
{
    public function __invoke(PlatformNotification $platformNotification): PlatformNotification
    {
        if (! $platformNotification->canBeSent()) {
            return $platformNotification->loadCount('deliveries');
        }

        return DB::transaction(function () use ($platformNotification): PlatformNotification {
            $platformNotification->forceFill([
                'status' => PlatformNotificationStatus::SENT,
                'sent_at' => now(),
            ])->save();

            $platformNotification->deliveries()->delete();

            $this->recipientQuery($platformNotification->target_scope)
                ->get()
                ->each(function (User $user) use ($platformNotification): void {
                    $user->notify(new OrganizationMessageNotification(
                        title: $platformNotification->title,
                        body: $platformNotification->body,
                        severity: $platformNotification->severity,
                    ));

                    PlatformNotificationDelivery::query()->create([
                        'platform_notification_id' => $platformNotification->id,
                        'user_id' => $user->id,
                        'organization_id' => $user->organization_id,
                        'status' => 'delivered',
                        'delivered_at' => now(),
                        'failure_reason' => null,
                        'metadata' => [],
                    ]);
                });

            return $platformNotification->refresh()->loadCount('deliveries');
        });
    }

    private function recipientQuery(string $targetScope): Builder
    {
        $query = User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'status',
                'locale',
                'organization_id',
                'last_login_at',
                'password',
                'remember_token',
            ])
            ->where('status', UserStatus::ACTIVE)
            ->where('role', '!=', UserRole::SUPERADMIN->value);

        return match ($targetScope) {
            'admins' => $query->whereIn('role', [
                UserRole::ADMIN->value,
                UserRole::MANAGER->value,
            ]),
            'tenants' => $query->where('role', UserRole::TENANT->value),
            default => $query,
        };
    }
}
