<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Project extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'building_id',
        'created_by',
        'name',
        'description',
        'type',
        'status',
        'priority',
        'budget',
        'actual_cost',
        'start_date',
        'end_date',
        'completed_at',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'completed_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    // Belongs To Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Has Many Relationships
    public function tasks(): HasMany
    {
        return $this->hasMany(EnhancedTask::class);
    }

    // Many-to-Many with Pivot Data
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot(['role', 'status', 'permissions', 'hourly_rate', 'joined_at', 'left_at', 'added_by'])
            ->withTimestamps()
            ->using(ProjectMember::class);
    }

    // Scoped member relationships
    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('status', 'active');
    }

    public function managers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'manager');
    }

    public function contractors(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'contractor');
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
        return $query->whereIn('status', ['planning', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverBudget($query)
    {
        return $query->whereColumn('actual_cost', '>', 'budget');
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    // ==================== ACCESSORS & MUTATORS ====================

    public function getBudgetRemainingAttribute(): float
    {
        return (float) ($this->budget - $this->actual_cost);
    }

    public function getIsOverBudgetAttribute(): bool
    {
        return $this->actual_cost > $this->budget;
    }

    public function getCompletionPercentageAttribute(): int
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }
        
        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        return (int) (($completedTasks / $totalTasks) * 100);
    }
}