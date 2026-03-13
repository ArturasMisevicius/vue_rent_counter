<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TaskAssignment Pivot Model
 * 
 * Manages the many-to-many relationship between Tasks and Users
 * with additional role and completion tracking
 */
class TaskAssignment extends Pivot
{
    protected $table = 'task_assignments';

    protected $fillable = [
        'task_id',
        'user_id',
        'role',
        'assigned_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the task
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if assignment is completed
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Mark as completed
     */
    public function markCompleted(?string $notes = null): void
    {
        $this->update([
            'completed_at' => now(),
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Get duration in hours
     */
    public function getDurationHours(): ?float
    {
        if (!$this->completed_at) {
            return null;
        }

        return $this->assigned_at->diffInHours($this->completed_at);
    }

    /**
     * Check if assignment is overdue
     */
    public function isOverdue(): bool
    {
        return !$this->isCompleted() && 
               $this->task->due_date && 
               $this->task->due_date->isPast();
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            'assignee' => 'Assignee',
            'reviewer' => 'Reviewer',
            'observer' => 'Observer',
            default => ucfirst($this->role),
        };
    }
}