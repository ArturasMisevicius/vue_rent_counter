<?php

namespace App\Models;

use App\Enums\SubscriptionDuration;
use App\Filament\Support\Localization\LocalizedCodeLabel;
use Database\Factories\SubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    /** @use HasFactory<SubscriptionPaymentFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'organization_id',
        'subscription_id',
        'duration',
        'amount',
        'currency',
        'paid_at',
        'reference',
        'created_at',
        'updated_at',
    ];

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

    public function durationLabel(): string
    {
        $duration = $this->duration;

        if ($duration instanceof SubscriptionDuration) {
            return $duration->label();
        }

        return LocalizedCodeLabel::translate('superadmin.relation_resources.subscription_payments.durations', $duration);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('paid_at')
            ->orderByDesc('id');
    }

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
            'subscription:id,organization_id,plan,status',
        ]);
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->withIndexRelations()
            ->latestFirst();
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->where('organization_id', (int) $organizationId);
    }
}
