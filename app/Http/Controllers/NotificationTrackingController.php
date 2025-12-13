<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlatformNotification;
use App\Models\PlatformNotificationRecipient;
use App\Services\PlatformNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationTrackingController extends Controller
{
    public function __construct(
        private PlatformNotificationService $notificationService
    ) {
        //
    }

    /**
     * Track notification read receipt
     */
    public function track(Request $request, PlatformNotification $notification, int $organizationId): Response
    {
        $recipient = $notification->recipients()
            ->where('organization_id', $organizationId)
            ->first();

        if ($recipient && $recipient->isSent()) {
            $this->notificationService->markAsRead($recipient);
        }

        // Return a 1x1 transparent pixel
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        
        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($pixel),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
