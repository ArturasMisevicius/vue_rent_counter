<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Time Entry Model - Track time spent on tasks
 * 
 * @property int $id
 * @property int $user_id
 * @property int $task_id
 * @property int|null $assignment_id
 * @property float $hours
 * @property string|null $description
 * @property array|null $metadata
 * @property \Carbon\Carbon $logged_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class TimeEntry extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'user_id',
        'task_id', 
        'assignment_id',
        'hours',
        'description',
        'metadata',
        'logged_at',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'metadata' => 'array',
        'logged_at' => 'datetime',
    ];

    /**
     * Get the user who logged this time
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the task this time was logged for
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the assignment this time was logged for
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class, 'assignment_id');
    }

    /**
     * Check if time entry is billable
     */
    public function isBillable(): bool
    {
        return $this->metadata['billable'] ?? false;
    }

    /**
     * Get hourly rate
     */
    public function getHourlyRate(): ?float
    {
        return $this->metadata['rate'] ?? null;
    }

    /**
     * Calculate total cost
     */
    public function getTotalCost(): float
    {
        $rate = $this->getHourlyRate();
        return $rate ? $this->hours * $rate : 0;
    }

    /**
     * Get category
     */
    public function getCategory(): ?string
    {
        return $this->metadata['category'] ?? null;
    }

    /**
     * Scope: Billable entries
     */
    public function scopeBillable($query)
    {
        return $query->whereJsonContains('metadata->billable', true);
    }

    /**
     * Scope: Entries for date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('logged_at', [$startDate, $endDate]);
    }

    /**
     * Scope: This week's entries
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('logged_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope: This month's entries
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('logged_at', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }
}