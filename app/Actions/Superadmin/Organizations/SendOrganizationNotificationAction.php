<?php

namespace App\Actions\Superadmin\Organizations;

use App\Actions\Superadmin\Notifications\SendPlatformNotificationAction;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class SendOrganizationNotificationAction
{
    public function __construct(
        private readonly SendPlatformNotificationAction $sendPlatformNotificationAction,
    ) {}

    public function handle(Organization $organization, array $attributes): PlatformNotification
    {
        /** @var array{title: string, body: string} $validated */
        $validated = Validator::make($attributes, [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ])->validate();

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
