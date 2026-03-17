<?php

namespace App\Models;

use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use Database\Factories\PlatformNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformNotification extends Model
{
    /** @use HasFactory<PlatformNotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'author_id',
        'title',
        'body',
        'severity',
        'status',
        'target_scope',
        'sent_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'severity' => PlatformNotificationSeverity::class,
            'status' => PlatformNotificationStatus::class,
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PlatformNotificationDelivery::class);
    }
}
