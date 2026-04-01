<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use HasFactory;

    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (TimeEntry $timeEntry): void {
            $project = Project::query()
                ->select(['id', 'status'])
                ->find($timeEntry->project_id);

            if ($project?->isReadOnly()) {
                throw ValidationException::withMessages([
                    'project_id' => 'Time entries cannot be logged against completed or cancelled projects.',
                ]);
            }
        });
    }

    protected $fillable = [
        'organization_id',
        'user_id',
        'task_id',
        'project_id',
        'assignment_id',
        'hours',
        'hourly_rate',
        'cost_amount',
        'description',
        'approval_status',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'metadata',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'cost_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'metadata' => 'array',
            'logged_at' => 'datetime',
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

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TaskAssignment::class, 'assignment_id');
    }
}
