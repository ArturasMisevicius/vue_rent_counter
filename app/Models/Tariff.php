<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tariff extends Model
{
    use HasFactory, Auditable;

    /**
     * Attributes to exclude from audit logging.
     *
     * @var array<int, string>
     */
    protected array $auditExclude = [
        'configuration',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'provider_id',
        'remote_id',
        'name',
        'configuration',
        'active_from',
        'active_until',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'active_from' => 'datetime',
            'active_until' => 'datetime',
        ];
    }

    /**
     * Append computed attributes for performance optimization.
     *
     * @var array<int, string>
     */
    protected $appends = ['is_currently_active'];

    /**
     * Get the provider this tariff belongs to.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Check if this is a manual tariff (not linked to a provider).
     */
    public function isManual(): bool
    {
        return is_null($this->provider_id);
    }

    /**
     * Check if this tariff is active on a given date.
     */
    public function isActiveOn(Carbon $date): bool
    {
        return $this->active_from <= $date
            && (is_null($this->active_until) || $this->active_until >= $date);
    }

    /**
     * Get the is_currently_active attribute.
     * Computed once per model instance for performance.
     *
     * @return bool
     */
    public function getIsCurrentlyActiveAttribute(): bool
    {
        return $this->isActiveOn(now());
    }

    /**
     * Check if this is a flat rate tariff.
     */
    public function isFlatRate(): bool
    {
        return ($this->configuration['type'] ?? null) === 'flat';
    }

    /**
     * Check if this is a time-of-use tariff.
     */
    public function isTimeOfUse(): bool
    {
        return ($this->configuration['type'] ?? null) === 'time_of_use';
    }

    /**
     * Get the flat rate if applicable.
     */
    public function getFlatRate(): ?float
    {
        return $this->isFlatRate() ? ($this->configuration['rate'] ?? null) : null;
    }

    /**
     * Scope a query to active tariffs on a given date.
     */
    public function scopeActive($query, $date = null)
    {
        $date = $date ?? now();
        
        return $query->where('active_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('active_until')
                  ->orWhere('active_until', '>=', $date);
            });
    }

    /**
     * Scope a query to tariffs for a specific provider.
     */
    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope a query to flat rate tariffs.
     */
    public function scopeFlatRate($query)
    {
        return $query->whereJsonContains('configuration->type', 'flat');
    }

    /**
     * Scope a query to time-of-use tariffs.
     */
    public function scopeTimeOfUse($query)
    {
        return $query->whereJsonContains('configuration->type', 'time_of_use');
    }
}
