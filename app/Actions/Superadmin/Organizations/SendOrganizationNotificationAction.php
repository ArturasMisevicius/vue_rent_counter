<?php

namespace App\Actions\Superadmin\Organizations;

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationDelivery;
use App\Notifications\Superadmin\OrganizationMessageNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SendOrganizationNotificationAction
{
    /**
     * @param  array{title: string, body: string, severity: string}  $attributes
     */
    public function __invoke(Organization $organization, array $attributes): PlatformNotification
    {
        $data = Validator::make($attributes, [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'severity' => ['required', Rule::enum(PlatformNotificationSeverity::class)],
        ])->validate();

        return DB::transaction(function () use ($organization, $data): PlatformNotification {
            $severity = PlatformNotificationSeverity::from($data['severity']);
            $notification = PlatformNotification::query()->create([
                'author_id' => auth()->id(),
                'title' => $data['title'],
                'body' => $data['body'],
                'severity' => $severity,
                'status' => PlatformNotificationStatus::SENT,
                'target_scope' => "organization:{$organization->id}",
                'sent_at' => now(),
                'metadata' => [
                    'organization_id' => $organization->id,
                ],
            ]);

            $organization->users()
                ->select(['id', 'name', 'email', 'role', 'organization_id', 'status', 'locale', 'last_login_at', 'password', 'remember_token'])
                ->where('status', UserStatus::ACTIVE)
                ->get()
                ->each(function ($user) use ($organization, $notification, $data, $severity): void {
                    $user->notify(new OrganizationMessageNotification(
                        title: $data['title'],
                        body: $data['body'],
                        severity: $severity,
                    ));

                    PlatformNotificationDelivery::query()->create([
                        'platform_notification_id' => $notification->id,
                        'user_id' => $user->id,
                        'organization_id' => $organization->id,
                        'status' => 'delivered',
                        'delivered_at' => now(),
                        'failure_reason' => null,
                        'metadata' => [],
                    ]);
                });

            return $notification->load('deliveries');
        });
    }
}
