<?php

namespace App\Filament\Actions\Superadmin\Notifications;

use App\Enums\PlatformNotificationStatus;
use App\Http\Requests\Superadmin\Notifications\SendPlatformNotificationRequest;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationRecipient;

class SavePlatformNotificationDraftAction
{
    public function handle(array $attributes): PlatformNotification
    {
        /** @var SendPlatformNotificationRequest $request */
        $request = new SendPlatformNotificationRequest;
        $validated = $request
            ->requireSeverity()
            ->validatePayload($attributes);

        $notification = PlatformNotification::query()->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'severity' => $validated['severity'],
            'status' => PlatformNotificationStatus::DRAFT,
        ]);

        if (($validated['target_mode'] ?? 'all') === 'specific') {
            $organizationIds = collect($validated['organization_ids'] ?? [])
                ->filter(fn (mixed $id): bool => is_numeric($id))
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values();

            Organization::query()
                ->select(['id', 'slug', 'owner_user_id'])
                ->with(['owner:id,email'])
                ->whereIn('id', $organizationIds)
                ->get()
                ->each(function (Organization $organization) use ($notification): void {
                    PlatformNotificationRecipient::query()->updateOrCreate(
                        [
                            'platform_notification_id' => $notification->id,
                            'organization_id' => $organization->id,
                        ],
                        [
                            'email' => $organization->owner?->email ?? 'notifications@'.$organization->slug.'.local',
                            'delivery_status' => 'pending',
                            'sent_at' => null,
                            'read_at' => null,
                            'failure_reason' => null,
                        ],
                    );
                });
        }

        return $notification;
    }
}
