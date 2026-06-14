<?php

declare(strict_types=1);

namespace App\Filament\Actions\Notifications;

use App\Enums\AuditLogAction;
use App\Filament\Support\Audit\AuditLogger;
use App\Filament\Support\Notifications\DomainNotificationCatalog;
use App\Filament\Support\Notifications\DomainNotificationContent;
use App\Mail\DomainNotificationMail;
use App\Models\NotificationDeliveryLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

final class DispatchDomainNotification
{
    /**
     * @param  array<string, mixed>  $extraData
     */
    public function handle(
        User $recipient,
        DomainNotificationContent $content,
        Organization|int|null $organization = null,
        ?Model $subject = null,
        ?User $actor = null,
        array $extraData = [],
    ): ?DatabaseNotification {
        if (! DomainNotificationCatalog::isSupported($content->type)) {
            return null;
        }

        $organizationId = $this->organizationId($organization, $recipient, $content);

        if (! $this->recipientCanReceive($recipient, $organizationId)) {
            return null;
        }

        $existing = $this->existingNotification($recipient, $content, $organizationId);

        if ($existing instanceof DatabaseNotification) {
            return $existing;
        }

        $notification = DatabaseNotification::query()->create([
            'id' => (string) Str::uuid(),
            'type' => $content->type,
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'organization_id' => $organizationId,
            'recipient_user_id' => $recipient->id,
            'title' => $content->title,
            'message' => $content->message,
            'action_url' => $content->actionUrl,
            'dedupe_key' => $content->dedupeKey,
            'data' => [
                'title' => $content->title,
                'body' => $content->message,
                'url' => $content->actionUrl,
                'business_type' => $content->type,
                ...$content->data,
                ...$extraData,
            ],
            'read_at' => null,
            'sent_email_at' => null,
        ]);

        $this->logDelivery($notification, 'database', 'delivered', deliveredAt: now());

        if ($content->sendEmail) {
            $this->sendEmail($notification, $recipient, $content);
        }

        $this->audit($notification, $content, $subject, $actor);

        return $notification;
    }

    private function existingNotification(
        User $recipient,
        DomainNotificationContent $content,
        ?int $organizationId,
    ): ?DatabaseNotification {
        if (blank($content->dedupeKey)) {
            return null;
        }

        return DatabaseNotification::query()
            ->where('recipient_user_id', $recipient->id)
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $recipient->id)
            ->where('type', $content->type)
            ->where('dedupe_key', $content->dedupeKey)
            ->when(
                $organizationId !== null,
                fn ($query) => $query->where('organization_id', $organizationId),
                fn ($query) => $query->whereNull('organization_id'),
            )
            ->first();
    }

    private function recipientCanReceive(User $recipient, ?int $organizationId): bool
    {
        if ($organizationId === null || $recipient->isSuperadmin()) {
            return true;
        }

        return (int) $recipient->organization_id === $organizationId;
    }

    private function organizationId(
        Organization|int|null $organization,
        User $recipient,
        DomainNotificationContent $content,
    ): ?int {
        if ($organization instanceof Organization) {
            return (int) $organization->getKey();
        }

        if (is_int($organization)) {
            return $organization;
        }

        $contentOrganizationId = $content->data['organization_id'] ?? null;

        if (is_numeric($contentOrganizationId)) {
            return (int) $contentOrganizationId;
        }

        return is_numeric($recipient->organization_id) ? (int) $recipient->organization_id : null;
    }

    private function sendEmail(
        DatabaseNotification $notification,
        User $recipient,
        DomainNotificationContent $content,
    ): void {
        $attemptedAt = now();
        $log = $this->logDelivery($notification, 'mail', 'attempted', attemptedAt: $attemptedAt);

        try {
            Mail::to($recipient->email)->send(new DomainNotificationMail(
                subjectLine: $content->subject(),
                greeting: $content->greeting(),
                title: $content->title,
                message: $content->message,
                actionUrl: $this->emailActionUrl($content->actionUrl),
                actionLabel: $content->actionLabel(),
            ));

            $deliveredAt = now();

            $notification->forceFill([
                'sent_email_at' => $deliveredAt,
            ])->save();

            $log->update([
                'status' => 'delivered',
                'delivered_at' => $deliveredAt,
            ]);
        } catch (Throwable $throwable) {
            $log->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => Str::limit($throwable->getMessage(), 1000, ''),
            ]);
        }
    }

    private function emailActionUrl(?string $actionUrl): ?string
    {
        if (blank($actionUrl)) {
            return null;
        }

        return str_starts_with($actionUrl, '/')
            ? url($actionUrl)
            : $actionUrl;
    }

    private function logDelivery(
        DatabaseNotification $notification,
        string $channel,
        string $status,
        mixed $attemptedAt = null,
        mixed $deliveredAt = null,
    ): NotificationDeliveryLog {
        return NotificationDeliveryLog::query()->create([
            'notification_id' => $notification->id,
            'channel' => $channel,
            'status' => $status,
            'attempted_at' => $attemptedAt ?? now(),
            'delivered_at' => $deliveredAt,
            'failed_at' => null,
            'error_message' => null,
        ]);
    }

    private function audit(
        DatabaseNotification $notification,
        DomainNotificationContent $content,
        ?Model $subject,
        ?User $actor,
    ): void {
        if (! $subject instanceof Model) {
            return;
        }

        app(AuditLogger::class)->record(
            AuditLogAction::SENT,
            $subject,
            [
                'context' => [
                    'mutation' => 'domain_notification.sent',
                    'notification_type' => $content->type,
                ],
                'notification' => [
                    'id' => $notification->id,
                    'recipient_user_id' => $notification->recipient_user_id,
                    'sent_email_at' => $notification->sent_email_at?->toISOString(),
                ],
            ],
            actorUserId: $actor?->id,
            description: "Domain notification sent: {$content->type}",
        );
    }
}
