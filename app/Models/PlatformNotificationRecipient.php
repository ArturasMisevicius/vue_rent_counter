<?php

namespace App\Models;

use Database\Factories\PlatformNotificationRecipientFactory;
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
}
