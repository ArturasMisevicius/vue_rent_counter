<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Platform Notification Model
 * 
 * Manages platform-wide notifications sent by superadmins to organizations.
 * Supports targeting all organizations, specific plans, or individual organizations.
 */
class PlatformNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'target_type',
        'target_criteria',
        'status',
        'scheduled_at',
        'sent_at',
        'created_by',
        'delivery_stats',
        'failure_reason',
    ];

    protected $casts = [
        'target_criteria' => 'array',
        'delivery_stats' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(PlatformNotificationRecipient::class);
    }

    // Status checks
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isReadyToSend(): bool
    {
        return $this->isScheduled() && 
               $this->scheduled_at && 
               $this->scheduled_at->isPast();
    }

    // Target organizations
    public function getTargetOrganizations(): \Illuminate\Database\Eloquent\Collection
    {
        return match ($this->target_type) {
            'all' => Organization::active()->get(),
            'plan' => Organization::active()->whereIn('plan', $this->target_criteria)->get(),
            'organization' => Organization::active()->whereIn('id', $this->target_criteria)->get(),
            default => collect(),
        };
    }

    // Delivery statistics
    public function getTotalRecipients(): int
    {
        return $this->recipients()->count();
    }

    public function getSentCount(): int
    {
        return $this->recipients()->where('delivery_status', 'sent')->count();
    }

    public function getFailedCount(): int
    {
        return $this->recipients()->where('delivery_status', 'failed')->count();
    }

    public function getReadCount(): int
    {
        return $this->recipients()->where('delivery_status', 'read')->count();
    }

    public function getDeliveryRate(): float
    {
        $total = $this->getTotalRecipients();
        if ($total === 0) {
            return 0;
        }
        
        return ($this->getSentCount() / $total) * 100;
    }

    public function getReadRate(): float
    {
        $sent = $this->getSentCount();
        if ($sent === 0) {
            return 0;
        }
        
        return ($this->getReadCount() / $sent) * 100;
    }

    // Actions
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    public function schedule(\Carbon\Carbon $scheduledAt): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    // Scopes
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeReadyToSend(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<=', now());
    }
}
