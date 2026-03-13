<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EnhancedTask extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'property_id',
        'meter_id',
        'created_by',
        'parent_task_id',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'estimated_hours',
        'actual_hours',
        'estimated_cost',
        'actual_cost',
        'due_date',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    // Belongs To Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Self-referencing relationships (parent-child tasks)
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function childTasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id');
    }

    // Recursive relationship for all descendants
    public function descendants(): HasMany
    {
        return $this->childTasks()->with('descendants');
    }

    // Task Assignments (Many-to-Many with Pivot)
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'task_id');
    }

    public function assignees(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            TaskAssignment::class,
            'task_id',
            'id',
            'id',
            'user_id'
        );
    }

    // Scoped assignment relationships
    public function activeAssignments(): HasMany
    {
        return $this->assignments()->where('status', 'assigned');
    }

    public function primaryAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')
            ->join('task_assignments', function ($join) {
                $join->on('users.id', '=', 'task_assignments.user_id')
                     ->where('task_assignments.task_id', $this->id)
                     ->where('task_assignments.role', 'assignee');
            });
    }

    // Polymorphic Relationships
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(EnhancedAttachment::class, 'attachable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot(['tagged_by'])
            ->withTimestamps();
    }

    // ==================== QUERY SCOPES ====================

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->whereHas('assignments', function ($q) use ($userId) {
            $q->where('user_id', $userId)->where('status', 'assigned');
        });
    }

    public function scopeRootTasks($query)
    {
        return $query->whereNull('parent_task_id');
    }

    public function scopeSubTasks($query)
    {
        return $query->whereNotNull('parent_task_id');
    }

    // ==================== ACCESSORS & MUTATORS ====================

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, ['completed', 'cancelled']);
    }

    public function getHoursRemainingAttribute(): float
    {
        return max(0, (float) ($this->estimated_hours - $this->actual_hours));
    }

    public function getCostVarianceAttribute(): float
    {
        return (float) ($this->actual_cost - $this->estimated_cost);
    }

    public function getCompletionPercentageAttribute(): int
    {
        if ($this->status === 'completed') {
            return 100;
        }
        
        if ($this->estimated_hours > 0) {
            return min(100, (int) (($this->actual_hours / $this->estimated_hours) * 100));
        }
        
        return 0;
    }

    // ==================== HELPER METHODS ====================

    public function canBeAssignedTo(User $user): bool
    {
        // Check if user has access to the tenant/project
        if ($this->project) {
            return $this->project->members()->where('user_id', $user->id)->exists();
        }
        
        return $user->tenant_id === $this->tenant_id;
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        
        // Update all assignments to completed
        $this->assignments()->update(['status' => 'completed']);
    }
}