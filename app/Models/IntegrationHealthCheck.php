<?php

namespace App\Models;

use App\Enums\IntegrationHealthStatus;
use Database\Factories\IntegrationHealthCheckFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegrationHealthCheck extends Model
{
    /** @use HasFactory<IntegrationHealthCheckFactory> */
    use HasFactory;

    private const OPERATIONS_PAGE_COLUMNS = [
        'id',
        'key',
        'label',
        'status',
        'summary',
        'details',
        'response_time_ms',
        'checked_at',
    ];

    protected $fillable = [
        'key',
        'label',
        'status',
        'checked_at',
        'response_time_ms',
        'summary',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'status' => IntegrationHealthStatus::class,
            'checked_at' => 'datetime',
            'details' => 'array',
        ];
    }

    public function scopeForOperationsPage(Builder $query): Builder
    {
        return $query
            ->select(self::OPERATIONS_PAGE_COLUMNS)
            ->orderBy('label');
    }

    public function hasTrippedCircuitBreaker(): bool
    {
        return (bool) data_get($this->details, 'circuit_breaker_tripped', false)
            || $this->status === IntegrationHealthStatus::FAILED;
    }
}
