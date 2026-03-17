<?php

namespace App\Models;

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use Database\Factories\PlatformNotificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformNotification extends Model
{
    /** @use HasFactory<PlatformNotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'severity',
        'status',
        'scheduled_for',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'severity' => PlatformNotificationSeverity::class,
            'status' => PlatformNotificationStatus::class,
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PlatformNotificationDelivery::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(PlatformNotificationRecipient::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->forStatus(PlatformNotificationStatus::DRAFT);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->forStatus(PlatformNotificationStatus::SCHEDULED);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->forStatus(PlatformNotificationStatus::SENT);
    }

    public function scopeForStatus(Builder $query, PlatformNotificationStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeDueToSend(Builder $query, ?\DateTimeInterface $moment = null): Builder
    {
        $sendAt = $moment ?? now();

        return $query
            ->scheduled()
            ->where('scheduled_for', '<=', $sendAt);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function scopeWithDeliverySummary(Builder $query): Builder
    {
        return $query->withCount([
            'deliveries',
            'recipients',
        ]);
    }

    public function scopeWithDeliveryRelations(Builder $query): Builder
    {
        return $query->with([
            'deliveries.user:id,name,email,role,status',
        ]);
    }
}
