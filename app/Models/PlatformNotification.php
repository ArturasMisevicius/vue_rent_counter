<?php

namespace App\Models;

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use Database\Factories\PlatformNotificationFactory;
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
}
