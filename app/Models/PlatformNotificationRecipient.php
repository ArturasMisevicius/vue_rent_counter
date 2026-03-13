<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform Notification Recipient Model
 * 
 * Tracks delivery status for individual notification recipients.
 */
class PlatformNotificationRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_notification_id',
        'organization_id',
        'email',
        'delivery_status',
        'sent_at',
        'read_at',
        'failure_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function notification(): BelongsTo
    {
        return $this->belongsTo(PlatformNotification::class, 'platform_notification_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    // Status checks
    public function isPending(): bool
    {
        return $this->delivery_status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->delivery_status === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->delivery_status === 'failed';
    }

    public function isRead(): bool
    {
        return $this->delivery_status === 'read';
    }

    // Actions
    public function markAsSent(): void
    {
        $this->update([
            'delivery_status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'delivery_status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'delivery_status' => 'read',
            'read_at' => now(),
        ]);
    }
}
