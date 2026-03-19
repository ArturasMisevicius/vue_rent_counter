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

    public function scopeRecent(Builder $query): Builder
    {
        return $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
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

    public function prunable(): Builder
    {
        return static::query()
            ->where('type', SecurityViolationType::DATA_ACCESS)
            ->where('metadata->source', 'csp-report')
            ->where('occurred_at', '<=', now()->subDays(14));
    }
}
