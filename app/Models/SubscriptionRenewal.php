<?php

namespace App\Models;

use Database\Factories\SubscriptionRenewalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionRenewal extends Model
{
    /** @use HasFactory<SubscriptionRenewalFactory> */
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'method',
        'period',
        'old_expires_at',
        'new_expires_at',
        'duration_days',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'old_expires_at' => 'datetime',
            'new_expires_at' => 'datetime',
            'duration_days' => 'integer',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAutomatic(): bool
    {
        return $this->method === 'automatic';
    }
}
