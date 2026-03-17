<?php

namespace App\Models;

use App\Enums\SubscriptionDuration;
use Database\Factories\SubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    /** @use HasFactory<SubscriptionPaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'subscription_id',
        'duration',
        'amount',
        'currency',
        'paid_at',
        'reference',
    ];

    protected function casts(): array
    {
        return [
            'duration' => SubscriptionDuration::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
