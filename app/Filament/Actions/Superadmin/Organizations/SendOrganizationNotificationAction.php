<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Filament\Actions\Superadmin\Notifications\SendPlatformNotificationAction;
use App\Http\Requests\Superadmin\Notifications\SendPlatformNotificationRequest;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\User;

class SendOrganizationNotificationAction
{
    public function __construct(
        private readonly SendPlatformNotificationAction $sendPlatformNotificationAction,
    ) {}

    public function handle(Organization $organization, array $attributes): PlatformNotification
    {
        /** @var SendPlatformNotificationRequest $request */
        $request = new SendPlatformNotificationRequest;
        $validated = $request->validatePayload($attributes);

        $notification = PlatformNotification::query()->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
        ]);

        $users = User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'locale', 'password', 'remember_token'])
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->get();

        return $this->sendPlatformNotificationAction->handle($notification, $users);
    }
}
