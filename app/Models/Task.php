<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasTags;
use App\Traits\HasComments;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Task Model - Individual work items within projects
 * 
 * Supports multiple assignees with different roles (assignee, reviewer, observer)
 */
class Task extends Model
{
    use HasFactory, BelongsToTenant, HasTags, HasComments, HasAttachments;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'title',
        'description',
        'status',
        'priority',
        'created_by',
        'due_date',
        'completed_at',
        'estimated_hours',
        'actual_hours',
        'checklist',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'checklist' => 'array',
    ];

    /**
     * Get the project this task belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created this task
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all users assigned to this task with roles
     */
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignments')
            ->using(TaskAssignment::class)
            ->withPivot(['role', 'assigned_at', 'completed_at', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get primary assignees
     */
    public function assignees(): BelongsToMany
    {
        return $this->assignedUsers()->wherePivot('role', 'assignee');
    }

    /**
     * Get reviewers
     */
    public function reviewers(): BelongsToMany
    {
        return $this->assignedUsers()->wherePivot('role', 'reviewer');
    }

    /**
     * Get observers
     */
    public function observers(): BelongsToMany
    {
        return $this->assignedUsers()->wherePivot('role', 'observer');
    }

    /**
     * Assign user to task with specific role
     */
    public function assignUser(User $user, string $role = 'assignee', ?string $notes = null): void
    {
        $this->assignedUsers()->attach($user->id, [
            'role' => $role,
            'assigned_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Mark task as completed by user
     */
    public function markCompletedBy(User $user): void
    {
        $this->assignedUsers()->updateExistingPivot($user->id, [
            'completed_at' => now(),
        ]);

        // If all assignees completed, mark task as completed
        $totalAssignees = $this->assignees()->count();
        $completedAssignees = $this->assignees()
            ->wherePivotNotNull('completed_at')
            ->count();

        if ($totalAssignees > 0 && $totalAssignees === $completedAssignees) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Get checklist completion percentage
     */
    public function getChecklistCompletion(): int
    {
        if (!$this->checklist || empty($this->checklist)) {
            return 0;
        }

        $total = count($this->checklist);
        $completed = collect($this->checklist)->where('completed', true)->count();

        return $total > 0 ? (int) round(($completed / $total) * 100) : 0;
    }

    /**
     * Update checklist item
     */
    public function updateChecklistItem(int $index, bool $completed): void
    {
        $checklist = $this->checklist ?? [];
        
        if (isset($checklist[$index])) {
            $checklist[$index]['completed'] = $completed;
            $this->checklist = $checklist;
            $this->save();
        }
    }

    /**
     * Scope: Tasks assigned to user
     */
    public function scopeAssignedTo($query, User $user)
    {
        return $query->whereHas('assignedUsers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    /**
     * Scope: Overdue tasks
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope: High priority tasks
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Check if task is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get estimated vs actual hours variance
     */
    public function getHoursVariance(): ?float
    {
        if (!$this->estimated_hours || !$this->actual_hours) {
            return null;
        }

        return $this->actual_hours - $this->estimated_hours;
    }
}