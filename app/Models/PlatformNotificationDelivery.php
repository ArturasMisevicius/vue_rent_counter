<?php

namespace App\Models;

use Database\Factories\PlatformNotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformNotificationDelivery extends Model
{
    /** @use HasFactory<PlatformNotificationDeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'platform_notification_id',
        'user_id',
        'channel',
        'delivered_at',
        'failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(PlatformNotification::class, 'platform_notification_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForNotification(Builder $query, int $notificationId): Builder
    {
        return $query->where('platform_notification_id', $notificationId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query
            ->whereNull('delivered_at')
            ->whereNull('failed_at');
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->whereNotNull('delivered_at');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereNotNull('failed_at');
    }

    public function scopeLatestAttemptFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('delivered_at')
            ->orderByDesc('failed_at')
            ->orderByDesc('id');
    }

    public function scopeWithNotificationSummary(Builder $query): Builder
    {
        return $query->with([
            'notification:id,title,severity,status,scheduled_for,sent_at',
            'user:id,name,email,role,status',
        ]);
    }
}
