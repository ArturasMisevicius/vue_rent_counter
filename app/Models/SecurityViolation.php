<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use Database\Factories\SecurityViolationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class SecurityViolation extends Model
{
    /** @use HasFactory<SecurityViolationFactory> */
    use HasFactory;

    use MassPrunable;

    private const DASHBOARD_COLUMNS = [
        'id',
        'organization_id',
        'user_id',
        'type',
        'severity',
        'ip_address',
        'summary',
        'metadata',
        'occurred_at',
        'resolved_at',
        'created_at',
        'updated_at',
    ];

    private const SUPERADMIN_FEED_COLUMNS = [
        'id',
        'organization_id',
        'user_id',
        'type',
        'severity',
        'ip_address',
        'summary',
        'metadata',
        'occurred_at',
        'resolved_at',
    ];

    private const INTEGRATION_HEALTH_FEED_COLUMNS = [
        'id',
        'organization_id',
        'type',
        'severity',
        'ip_address',
        'summary',
        'metadata',
        'occurred_at',
    ];

    protected $fillable = [
        'organization_id',
        'user_id',
        'type',
        'severity',
        'ip_address',
        'summary',
        'metadata',
        'occurred_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => SecurityViolationType::class,
            'severity' => SecurityViolationSeverity::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activeBlockedIpAddresses(): HasMany
    {
        return $this->hasMany(BlockedIpAddress::class, 'ip_address', 'ip_address')
            ->active();
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType(Builder $query, SecurityViolationType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOfSeverity(Builder $query, SecurityViolationSeverity $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeOccurredSince(Builder $query, \DateTimeInterface $dateTime): Builder
    {
        return $query->where('occurred_at', '>=', $dateTime);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeReviewed(Builder $query): Builder
    {
        return $query->whereNotNull('metadata->review->reviewed_at');
    }

    public function scopeUnreviewed(Builder $query): Builder
    {
        return $query->whereNull('metadata->review->reviewed_at');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    public function scopeMatchingSearch(Builder $query, ?string $search): Builder
    {
        $term = trim((string) $search);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->where('summary', 'like', '%'.$term.'%')
                ->orWhere('ip_address', 'like', '%'.$term.'%')
                ->orWhereRelation('organization', 'name', 'like', '%'.$term.'%')
                ->orWhereRelation('user', 'name', 'like', '%'.$term.'%')
                ->orWhereRelation('user', 'email', 'like', '%'.$term.'%');
        });
    }

    public function scopeForTypeValue(Builder $query, ?string $type): Builder
    {
        if (blank($type)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    public function scopeForSeverityValue(Builder $query, ?string $severity): Builder
    {
        if (blank($severity)) {
            return $query;
        }

        return $query->where('severity', $severity);
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->where('organization_id', $organizationId);
    }

    public function scopeForResolutionStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'resolved' => $query->resolved(),
            'unresolved' => $query->unresolved(),
            default => $query,
        };
    }

    public function scopeForReviewStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'reviewed' => $query->reviewed(),
            'unreviewed' => $query->unreviewed(),
            default => $query,
        };
    }

    public function scopeOccurredBetween(Builder $query, mixed $from, mixed $to): Builder
    {
        if (filled($from)) {
            $query->where('occurred_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if (filled($to)) {
            $query->where('occurred_at', '<=', Carbon::parse($to)->endOfDay());
        }

        return $query;
    }

    public function scopeWithDashboardRelations(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
            'user:id,organization_id,name,email,role,status',
        ]);
    }

    public function scopeForDashboard(Builder $query): Builder
    {
        return $query
            ->select(self::DASHBOARD_COLUMNS)
            ->withDashboardRelations()
            ->recent();
    }

    public function scopeForSuperadminFeed(Builder $query): Builder
    {
        return $query
            ->select(self::SUPERADMIN_FEED_COLUMNS)
            ->with([
                'user:id,name,email',
            ])
            ->recent();
    }

    public function scopeForIntegrationHealthFeed(Builder $query): Builder
    {
        return $query
            ->select(self::INTEGRATION_HEALTH_FEED_COLUMNS)
            ->with([
                'organization:id,name',
            ])
            ->recent();
    }

    public function hasActiveIpBlock(): bool
    {
        if ($this->relationLoaded('activeBlockedIpAddresses')) {
            return $this->activeBlockedIpAddresses->isNotEmpty();
        }

        return $this->activeBlockedIpAddresses()->exists();
    }

    public function activeBlockedUntil(): ?Carbon
    {
        if ($this->relationLoaded('activeBlockedIpAddresses')) {
            return $this->activeBlockedIpAddresses->first()?->blocked_until;
        }

        return $this->activeBlockedIpAddresses()
            ->first()?->blocked_until;
    }

    public function isReviewed(): bool
    {
        return filled(data_get($this->metadata, 'review.reviewed_at'));
    }

    public function reviewNote(): ?string
    {
        $note = data_get($this->metadata, 'review.note');

        return is_string($note) && $note !== '' ? $note : null;
    }

    public function markAsReviewed(User $reviewer, ?string $note = null): void
    {
        $metadata = $this->metadata ?? [];

        data_set($metadata, 'review', [
            'reviewed_at' => now()->toIso8601String(),
            'reviewed_by_user_id' => $reviewer->getKey(),
            'note' => blank($note) ? null : $note,
        ]);

        $this->forceFill([
            'metadata' => $metadata,
        ])->save();
    }

    public function prunable(): Builder
    {
        return static::query()
            ->where('type', SecurityViolationType::DATA_ACCESS)
            ->where('metadata->source', 'csp-report')
            ->where('occurred_at', '<=', now()->subDays(14));
    }
}
