<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformNotificationDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_notification_id',
        'user_id',
        'organization_id',
        'status',
        'delivered_at',
        'failure_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'metadata' => 'array',
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
