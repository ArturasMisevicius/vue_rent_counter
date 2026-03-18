<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Actions\Notifications\MarkPlatformNotificationReadAction;
use App\Models\PlatformNotificationRecipient;
use Illuminate\Http\JsonResponse;

final class NotificationTrackingController extends Controller
{
    public function __invoke(
        PlatformNotificationRecipient $platformNotificationRecipient,
        MarkPlatformNotificationReadAction $markPlatformNotificationReadAction,
    ): JsonResponse {
        $user = request()->user();

        abort_unless($user !== null, 401);

        $recipient = $markPlatformNotificationReadAction->handle($platformNotificationRecipient, $user);

        $unreadCount = $user->organization_id === null
            ? 0
            : PlatformNotificationRecipient::query()
                ->forOrganization($user->organization_id)
                ->sent()
                ->unread()
                ->count();

        return response()->json([
            'tracked' => true,
            'recipient_id' => $recipient->id,
            'read_at' => $recipient->read_at?->toIso8601String(),
            'unread_count' => $unreadCount,
        ]);
    }
}
