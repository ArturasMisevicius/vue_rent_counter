<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemHealthMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'metric_type',
        'metric_name',
        'value',
        'status',
        'checked_at',
    ];

    protected $casts = [
        'value' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Check if the metric indicates a healthy status.
     */
    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    /**
     * Check if the metric indicates a warning status.
     */
    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }

    /**
     * Check if the metric indicates a danger status.
     */
    public function isDanger(): bool
    {
        return $this->status === 'danger';
    }

    /**
     * Get the color for the status indicator.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'danger' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get the icon for the status indicator.
     */
    public function getStatusIcon(): string
    {
        return match ($this->status) {
            'healthy' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'danger' => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Scope to get latest metrics by type.
     */
    public function scopeLatestByType($query, string $type)
    {
        return $query->where('metric_type', $type)
            ->orderBy('checked_at', 'desc')
            ->limit(1);
    }

    /**
     * Scope to get metrics within a time range.
     */
    public function scopeWithinTimeRange($query, $from, $to)
    {
        return $query->whereBetween('checked_at', [$from, $to]);
    }

    /**
     * Scope to get unhealthy metrics.
     */
    public function scopeUnhealthy($query)
    {
        return $query->whereIn('status', ['warning', 'danger']);
    }
}
