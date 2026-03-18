<?php

declare(strict_types=1);

namespace App\Filament\Actions\Notifications;

use App\Models\PlatformNotificationRecipient;
use App\Models\User;

final class MarkPlatformNotificationReadAction
{
    public function handle(PlatformNotificationRecipient $recipient, User $user): PlatformNotificationRecipient
    {
        abort_unless(
            $user->isSuperadmin() || $user->organization_id === $recipient->organization_id,
            403,
        );

        if ($recipient->read_at === null) {
            $recipient->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return $recipient->refresh();
    }
}
