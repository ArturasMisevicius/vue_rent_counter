<?php

namespace App\Models;

use Database\Factories\PlatformNotificationRecipientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformNotificationRecipient extends Model
{
    /** @use HasFactory<PlatformNotificationRecipientFactory> */
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

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(PlatformNotification::class, 'platform_notification_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForNotification(Builder $query, int $notificationId): Builder
    {
        return $query->where('platform_notification_id', $notificationId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('delivery_status', 'pending');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('delivery_status', 'failed');
    }

    public function scopeLatestSentFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('sent_at')
            ->orderByDesc('id');
    }
}
