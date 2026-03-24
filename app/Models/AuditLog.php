<?php

namespace App\Models;

use App\Enums\AuditLogAction;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

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
        'ip_address',
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
        'ip_address',
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

    public function scopeWhereActorMatches(Builder $query, ?string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        $search = trim($search);

        return $query->whereHas('actor', function (Builder $actorQuery) use ($search): Builder {
            return $actorQuery->where(function (Builder $actorQuery) use ($search): Builder {
                return $actorQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        });
    }

    public function scopeForPresentedActionType(Builder $query, ?string $actionType): Builder
    {
        if (blank($actionType)) {
            return $query;
        }

        return match ($actionType) {
            'finalized' => $query->where('metadata->context->mutation', 'invoice.finalized'),
            'payment_processed' => $query->where('metadata->context->mutation', 'invoice.payment_recorded'),
            AuditLogAction::UPDATED->value => $query
                ->where('action', AuditLogAction::UPDATED)
                ->where(function (Builder $query): Builder {
                    return $query
                        ->whereNull('metadata->context->mutation')
                        ->orWhere('metadata->context->mutation', '!=', 'invoice.payment_recorded');
                }),
            AuditLogAction::APPROVED->value => $query
                ->where('action', AuditLogAction::APPROVED)
                ->where(function (Builder $query): Builder {
                    return $query
                        ->whereNull('metadata->context->mutation')
                        ->orWhere('metadata->context->mutation', '!=', 'invoice.finalized');
                }),
            default => $query->where('action', $actionType),
        };
    }

    public function scopeForSubjectTypeValue(Builder $query, ?string $subjectType): Builder
    {
        if (blank($subjectType)) {
            return $query;
        }

        return $query->where('subject_type', $subjectType);
    }

    public function scopeOccurredBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when(
                filled($from),
                fn (Builder $query): Builder => $query->whereDate('occurred_at', '>=', $from),
            )
            ->when(
                filled($to),
                fn (Builder $query): Builder => $query->whereDate('occurred_at', '<=', $to),
            );
    }

    /**
     * @return array<string, string>
     */
    public static function subjectTypeOptions(): array
    {
        return static::query()
            ->select(['subject_type'])
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->mapWithKeys(fn (?string $subjectType): array => $subjectType === null
                ? []
                : [$subjectType => Str::of(class_basename($subjectType))->headline()->toString()])
            ->all();
    }
}
