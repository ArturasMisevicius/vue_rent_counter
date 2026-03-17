<?php

namespace App\Models;

use App\Enums\AuditLogAction;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    private const FEED_COLUMNS = [
        'id',
        'organization_id',
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
        'occurred_at',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditLogAction::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForAction(Builder $query, AuditLogAction $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeForSubject(Builder $query, Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject::class)
            ->where('subject_id', $subject->getKey());
    }

    public function scopeOccurredSince(Builder $query, \DateTimeInterface $dateTime): Builder
    {
        return $query->where('occurred_at', '>=', $dateTime);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    public function scopeWithActorSummary(Builder $query): Builder
    {
        return $query->with([
            'actor:id,organization_id,name,email,role,status',
        ]);
    }

    public function scopeWithOrganizationSummary(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
        ]);
    }

    public function scopeForAuditFeed(Builder $query): Builder
    {
        return $query
            ->select(self::FEED_COLUMNS)
            ->withActorSummary()
            ->withOrganizationSummary()
            ->recent();
    }
}
