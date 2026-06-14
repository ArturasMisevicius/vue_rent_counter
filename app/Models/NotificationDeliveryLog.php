<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NotificationDeliveryLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;

class NotificationDeliveryLog extends Model
{
    /** @use HasFactory<NotificationDeliveryLogFactory> */
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'channel',
        'status',
        'attempted_at',
        'delivered_at',
        'failed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(DatabaseNotification::class, 'notification_id');
    }
}
