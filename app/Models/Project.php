<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasTags;
use App\Traits\HasComments;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Project Model - Maintenance and improvement projects
 * 
 * Projects can be attached to Properties, Buildings, or Organizations
 * Supports polymorphic relationships for flexible project scoping
 */
class Project extends Model
{
    use HasFactory, BelongsToTenant, HasTags, HasComments, HasAttachments;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'type',
        'scope',
        'projectable_type',
        'projectable_id',
        'created_by',
        'assigned_to',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'budget',
        'actual_cost',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get the parent projectable model (Property, Building, etc.)
     */
    public function projectable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this project
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to this project
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all tasks for this project
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get all users involved in this project through tasks
     */
    public function involvedUsers(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            TaskAssignment::class,
            'task_id',
            'id',
            'id',
            'user_id'
        )->join('tasks', 'task_assignments.task_id', '=', 'tasks.id')
         ->where('tasks.project_id', $this->id)
         ->distinct();
    }

    /**
     * Scope: Projects for specific property
     */
    public function scopeForProperty($query, Property $property)
    {
        return $query->where('projectable_type', Property::class)
                    ->where('projectable_id', $property->id);
    }

    /**
     * Scope: Projects for specific building
     */
    public function scopeForBuilding($query, Building $building)
    {
        return $query->where('projectable_type', Building::class)
                    ->where('projectable_id', $building->id);
    }

    /**
     * Scope: Active projects
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Overdue projects
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Check if project is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Get completion percentage based on tasks
     */
    public function getCompletionPercentage(): int
    {
        $totalTasks = $this->tasks()->count();
        
        if ($totalTasks === 0) {
            return 0;
        }
        
        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        
        return (int) round(($completedTasks / $totalTasks) * 100);
    }

    /**
     * Get budget utilization percentage
     */
    public function getBudgetUtilization(): float
    {
        if (!$this->budget || $this->budget <= 0) {
            return 0;
        }

        return ($this->actual_cost / $this->budget) * 100;
    }

    /**
     * Check if project is over budget
     */
    public function isOverBudget(): bool
    {
        return $this->budget && $this->actual_cost > $this->budget;
    }
}