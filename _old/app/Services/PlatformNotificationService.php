<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationRecipient;
use App\Notifications\PlatformNotificationEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Platform Notification Service
 * 
 * Handles sending platform-wide notifications to organizations.
 */
class PlatformNotificationService
{
    public function sendNotification(PlatformNotification $notification): void
    {
        DB::transaction(function () use ($notification) {
            try {
                // Get target organizations
                $organizations = $notification->getTargetOrganizations();
                
                if ($organizations->isEmpty()) {
                    throw new \Exception('No target organizations found');
                }

                // Create recipient records
                $recipients = [];
                foreach ($organizations as $organization) {
                    $recipients[] = [
                        'platform_notification_id' => $notification->id,
                        'organization_id' => $organization->id,
                        'email' => $organization->email,
                        'delivery_status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                PlatformNotificationRecipient::insert($recipients);

                // Send emails
                $this->sendEmails($notification);

                // Update notification status
                $notification->markAsSent();

                Log::info('Platform notification sent', [
                    'notification_id' => $notification->id,
                    'title' => $notification->title,
                    'recipients_count' => count($recipients),
                ]);

            } catch (\Exception $e) {
                $notification->markAsFailed($e->getMessage());
                Log::error('Failed to send platform notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    public function scheduleNotification(PlatformNotification $notification, Carbon $scheduledAt): void
    {
        $notification->schedule($scheduledAt);
        
        Log::info('Platform notification scheduled', [
            'notification_id' => $notification->id,
            'title' => $notification->title,
            'scheduled_at' => $scheduledAt->toISOString(),
        ]);
    }

    public function processScheduledNotifications(): void
    {
        $notifications = PlatformNotification::readyToSend()->get();

        foreach ($notifications as $notification) {
            try {
                $this->sendNotification($notification);
            } catch (\Exception $e) {
                Log::error('Failed to process scheduled notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function sendEmails(PlatformNotification $notification): void
    {
        $recipients = $notification->recipients()->where('delivery_status', 'pending')->get();

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)
                    ->send(new PlatformNotificationEmail($notification, $recipient->organization));

                $recipient->markAsSent();

            } catch (\Exception $e) {
                $recipient->markAsFailed($e->getMessage());
                
                Log::warning('Failed to send email to recipient', [
                    'notification_id' => $notification->id,
                    'recipient_id' => $recipient->id,
                    'email' => $recipient->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function getNotificationStats(PlatformNotification $notification): array
    {
        return [
            'total_recipients' => $notification->getTotalRecipients(),
            'sent_count' => $notification->getSentCount(),
            'failed_count' => $notification->getFailedCount(),
            'read_count' => $notification->getReadCount(),
            'delivery_rate' => $notification->getDeliveryRate(),
            'read_rate' => $notification->getReadRate(),
        ];
    }

    public function markAsRead(PlatformNotificationRecipient $recipient): void
    {
        if ($recipient->isSent()) {
            $recipient->markAsRead();
        }
    }
}