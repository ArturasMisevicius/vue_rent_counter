# Advanced Eloquent Relationships Implementation

## Overview

This document provides a comprehensive implementation of advanced Eloquent relationships for the property management system, building on the existing models and adding complex relationship patterns.

## Scenario: Multi-Tenant Property Management System

### Entities
- **Users**: Multi-role (superadmin, admin, manager, tenant)
- **Organizations**: Property management companies
- **Properties**: Real estate units
- **Buildings**: Property containers
- **Tenants**: Property renters
- **Projects**: Maintenance/improvement initiatives
- **Tasks**: Work items within projects
- **Tags**: Polymorphic categorization
- **Comments**: Nested discussion system
- **Files/Attachments**: Document management
- **Permissions**: Role-based access control

### Complex Relationships Needed
1. Users belong to multiple Organizations with different roles
2. Projects can be personal or organizational
3. Tasks can be assigned to multiple users with different responsibilities
4. Tags are polymorphic across multiple models
5. Comments can be nested infinitely
6. Files can be attached to anything
7. Permissions cascade through hierarchies

## 1. Database Migrations

### User-Organization Pivot with Roles
```php
// Migration: create_organization_user_table.php
Schema::create('organization_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('role'); // admin, manager, viewer
    $table->json('permissions')->nullable();
    $table->timestamp('joined_at');
    $table->timestamp('left_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('invited_by')->nullable()->constrained('users');
    $table->timestamps();
    
    $table->unique(['organization_id', 'user_id', 'role']);
    $table->index(['user_id', 'is_active']);
});
```

### Projects Table
```php
// Migration: create_projects_table.php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->enum('type', ['maintenance', 'improvement', 'inspection']);
    $table->enum('scope', ['property', 'building', 'organization']);
    $table->morphs('projectable'); // property_id/building_id + type
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('assigned_to')->nullable()->constrained('users');
    $table->enum('status', ['draft', 'active', 'completed', 'cancelled']);
    $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
    $table->date('start_date')->nullable();
    $table->date('due_date')->nullable();
    $table->date('completed_at')->nullable();
    $table->decimal('budget', 10, 2)->nullable();
    $table->decimal('actual_cost', 10, 2)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->index(['tenant_id', 'status']);
    $table->index(['projectable_type', 'projectable_id']);
});
```

### Tasks Table with Multiple Assignees
```php
// Migration: create_tasks_table.php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('status', ['pending', 'in_progress', 'review', 'completed', 'cancelled']);
    $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
    $table->foreignId('created_by')->constrained('users');
    $table->date('due_date')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->integer('estimated_hours')->nullable();
    $table->integer('actual_hours')->nullable();
    $table->json('checklist')->nullable();
    $table->timestamps();
    
    $table->index(['project_id', 'status']);
});

// Task Assignments Pivot
Schema::create('task_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('task_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('role', ['assignee', 'reviewer', 'observer']);
    $table->timestamp('assigned_at');
    $table->timestamp('completed_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    
    $table->unique(['task_id', 'user_id', 'role']);
});
```

### Enhanced Taggables Table
```php
// Migration: create_taggables_table.php (already exists, enhanced)
Schema::table('taggables', function (Blueprint $table) {
    $table->foreignId('tagged_by')->nullable()->constrained('users');
    $table->timestamp('tagged_at')->useCurrent();
    $table->json('context')->nullable(); // Additional metadata
});
```

### Nested Comments Enhancement
```php
// Migration: enhance_comments_table.php
Schema::table('comments', function (Blueprint $table) {
    $table->integer('depth')->default(0);
    $table->string('path')->nullable(); // Materialized path for efficient queries
    $table->integer('sort_order')->default(0);
    $table->json('mentions')->nullable(); // @user mentions
    $table->boolean('is_resolved')->default(false);
    $table->foreignId('resolved_by')->nullable()->constrained('users');
    $table->timestamp('resolved_at')->nullable();
});
```

### File Attachments Enhancement
```php
// Migration: enhance_attachments_table.php
Schema::table('attachments', function (Blueprint $table) {
    $table->string('category')->nullable(); // invoice, photo, document
    $table->json('processing_status')->nullable(); // For image/video processing
    $table->string('thumbnail_path')->nullable();
    $table->json('exif_data')->nullable();
    $table->boolean('is_public')->default(false);
    $table->timestamp('expires_at')->nullable();
});
```

## 2. Model Definitions

### Enhanced User Model with Organization Relationships
```php
// app/Models/User.php (additions)
class User extends Authenticatable implements FilamentUser
{
    // ... existing code ...

    /**
     * Organizations this user belongs to with roles
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['role', 'permissions', 'joined_at', 'left_at', 'is_active', 'invited_by'])
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    /**
     * All organization memberships including inactive
     */
    public function organizationMemberships(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot(['role', 'permissions', 'joined_at', 'left_at', 'is_active', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * Projects created by this user
     */
    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    /**
     * Projects assigned to this user
     */
    public function assignedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'assigned_to');
    }

    /**
     * Tasks assigned to this user with roles
     */
    public function taskAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments')
            ->withPivot(['role', 'assigned_at', 'completed_at', 'notes'])
            ->withTimestamps();
    }

    /**
     * Tasks where user is the primary assignee
     */
    public function assignedTasks(): BelongsToMany
    {
        return $this->taskAssignments()->wherePivot('role', 'assignee');
    }

    /**
     * Tasks where user is a reviewer
     */
    public function reviewTasks(): BelongsToMany
    {
        return $this->taskAssignments()->wherePivot('role', 'reviewer');
    }

    /**
     * Get user's role in a specific organization
     */
    public function getRoleInOrganization(Organization $organization): ?string
    {
        $membership = $this->organizations()
            ->where('organization_id', $organization->id)
            ->first();
            
        return $membership?->pivot->role;
    }

    /**
     * Check if user has role in organization
     */
    public function hasRoleInOrganization(Organization $organization, string $role): bool
    {
        return $this->organizations()
            ->where('organization_id', $organization->id)
            ->wherePivot('role', $role)
            ->exists();
    }

    /**
     * Get all projects across all organizations
     */
    public function allProjects(): Builder
    {
        $organizationIds = $this->organizations()->pluck('organizations.id');
        
        return Project::whereIn('tenant_id', $organizationIds)
            ->orWhere('created_by', $this->id)
            ->orWhere('assigned_to', $this->id);
    }
}
```

### Project Model with Polymorphic Relationships
```php
// app/Models/Project.php
<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasTags;
use App\Traits\HasComments;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use BelongsToTenant, HasTags, HasComments, HasAttachments;

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
}
```

### Task Model with Multiple Assignees
```php
// app/Models/Task.php
<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasTags;
use App\Traits\HasComments;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use BelongsToTenant, HasTags, HasComments, HasAttachments;

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
}
```

## 3. Pivot Model Classes

### OrganizationUser Pivot Model
```php
// app/Models/OrganizationUser.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationUser extends Pivot
{
    protected $table = 'organization_user';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'permissions',
        'joined_at',
        'left_at',
        'is_active',
        'invited_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who invited this member
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Add permission
     */
    public function addPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->permissions = $permissions;
            $this->save();
        }
    }

    /**
     * Remove permission
     */
    public function removePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $this->permissions = array_values(array_diff($permissions, [$permission]));
        $this->save();
    }
}
```

### TaskAssignment Pivot Model
```php
// app/Models/TaskAssignment.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
```

## 4. Enhanced Traits

### HasComments Trait Enhancement
```php
// app/Traits/HasComments.php
<?php

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * Get all comments for the model
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->orderBy('path')
            ->orderBy('sort_order');
    }

    /**
     * Get only top-level comments
     */
    public function topLevelComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    /**
     * Get only internal comments
     */
    public function internalComments(): MorphMany
    {
        return $this->comments()->where('is_internal', true);
    }

    /**
     * Get only public comments
     */
    public function publicComments(): MorphMany
    {
        return $this->comments()->where('is_internal', false);
    }

    /**
     * Add a comment
     */
    public function addComment(string $body, ?User $user = null, bool $isInternal = false): Comment
    {
        return $this->comments()->create([
            'tenant_id' => $this->tenant_id ?? auth()->user()?->tenant_id,
            'user_id' => $user?->id ?? auth()->id(),
            'body' => $body,
            'is_internal' => $isInternal,
            'depth' => 0,
            'path' => null, // Will be set after creation
        ]);
    }

    /**
     * Get comment count
     */
    public function getCommentCountAttribute(): int
    {
        return $this->comments()->count();
    }

    /**
     * Get unresolved comment count
     */
    public function getUnresolvedCommentCountAttribute(): int
    {
        return $this->comments()->where('is_resolved', false)->count();
    }
}
```

### HasAttachments Trait Enhancement
```php
// app/Traits/HasAttachments.php
<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;

trait HasAttachments
{
    /**
     * Get all attachments for the model
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get only image attachments
     */
    public function images(): MorphMany
    {
        return $this->attachments()->where('mime_type', 'like', 'image/%');
    }

    /**
     * Get only document attachments
     */
    public function documents(): MorphMany
    {
        return $this->attachments()->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    /**
     * Attach a file
     */
    public function attachFile(
        UploadedFile $file, 
        ?string $description = null, 
        ?string $category = null,
        ?User $uploader = null
    ): Attachment {
        $filename = $file->hashName();
        $path = $file->store('attachments', 'public');

        return $this->attachments()->create([
            'tenant_id' => $this->tenant_id ?? auth()->user()?->tenant_id,
            'uploaded_by' => $uploader?->id ?? auth()->id(),
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => 'public',
            'path' => $path,
            'description' => $description,
            'category' => $category,
        ]);
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCountAttribute(): int
    {
        return $this->attachments()->count();
    }

    /**
     * Get total attachment size in bytes
     */
    public function getTotalAttachmentSizeAttribute(): int
    {
        return $this->attachments()->sum('size');
    }
}
```

## 5. Querying Examples

### Complex Relationship Queries
```php
// Get all projects for a user across all organizations
$userProjects = Project::whereHas('creator.organizations', function ($query) use ($user) {
    $query->whereHas('users', function ($q) use ($user) {
        $q->where('user_id', $user->id);
    });
})->orWhereHas('tasks.assignedUsers', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->with(['projectable', 'tasks.assignedUsers'])->get();

// Get tasks with assignees and their roles
$tasksWithAssignees = Task::with([
    'assignedUsers' => function ($query) {
        $query->withPivot(['role', 'assigned_at', 'completed_at']);
    },
    'project.projectable'
])->where('status', 'active')->get();

// Get all comments in a thread (nested)
$commentsThread = Comment::where('commentable_type', Property::class)
    ->where('commentable_id', $propertyId)
    ->with(['user', 'replies.user', 'replies.replies.user'])
    ->whereNull('parent_id')
    ->orderBy('created_at')
    ->get();

// Load tags for multiple model types efficiently
$taggedItems = collect();
$taggedItems = $taggedItems->merge(
    Property::withAnyTags(['maintenance', 'urgent'])->get()
)->merge(
    Project::withAnyTags(['maintenance', 'urgent'])->get()
);

// Complex whereHas with multiple conditions
$complexQuery = Property::whereHas('tenants', function ($query) {
    $query->where('lease_end', '>', now()->addMonths(3));
})->whereHas('projects', function ($query) {
    $query->where('status', 'active')
          ->whereHas('tasks', function ($q) {
              $q->where('priority', 'high');
          });
})->whereDoesntHave('comments', function ($query) {
    $query->where('is_resolved', false)
          ->where('created_at', '>', now()->subDays(7));
})->get();
```

### Relationship Existence Queries
```php
// Properties with active projects but no overdue tasks
$properties = Property::whereHas('projects', function ($query) {
    $query->where('status', 'active');
})->whereDoesntHave('projects.tasks', function ($query) {
    $query->where('due_date', '<', now())
          ->whereNotIn('status', ['completed', 'cancelled']);
})->get();

// Users with specific role in any organization
$admins = User::whereHas('organizations', function ($query) {
    $query->wherePivot('role', 'admin')
          ->wherePivot('is_active', true);
})->get();

// Projects with all tasks completed
$completedProjects = Project::whereDoesntHave('tasks', function ($query) {
    $query->whereNotIn('status', ['completed', 'cancelled']);
})->where('status', 'active')->get();
```

## 6. Performance Optimization

### Eager Loading Strategies
```php
// Efficient loading of complex relationships
$projects = Project::with([
    'projectable:id,address,type', // Only load needed columns
    'tasks' => function ($query) {
        $query->select(['id', 'project_id', 'title', 'status'])
              ->where('status', '!=', 'cancelled');
    },
    'tasks.assignedUsers:id,name,email',
    'tags:id,name,color',
    'comments' => function ($query) {
        $query->whereNull('parent_id') // Only top-level comments
              ->with('user:id,name')
              ->latest()
              ->limit(5);
    }
])->get();

// Subquery relationships for counts
$properties = Property::withCount([
    'projects',
    'projects as active_projects_count' => function ($query) {
        $query->where('status', 'active');
    },
    'comments as unresolved_comments_count' => function ($query) {
        $query->where('is_resolved', false);
    }
])->get();

// Avoiding N+1 with pivot data
$users = User::with([
    'organizations' => function ($query) {
        $query->withPivot(['role', 'joined_at', 'permissions']);
    }
])->get();
```

### Custom Relationship Macros
```php
// In AppServiceProvider boot method
Builder::macro('withActiveProjects', function () {
    return $this->with(['projects' => function ($query) {
        $query->where('status', 'active');
    }]);
});

// Usage
$properties = Property::withActiveProjects()->get();
```

## 7. Testing Relationships

### Factory Relationships
```php
// PropertyFactory.php
public function withProjects(int $count = 3): static
{
    return $this->afterCreating(function (Property $property) use ($count) {
        Project::factory()
            ->count($count)
            ->for($property, 'projectable')
            ->create(['tenant_id' => $property->tenant_id]);
    });
}

// Usage in tests
$property = Property::factory()
    ->withProjects(5)
    ->create();

// Test relationship integrity
test('property can have multiple projects', function () {
    $property = Property::factory()->create();
    $projects = Project::factory()
        ->count(3)
        ->for($property, 'projectable')
        ->create();

    expect($property->projects)->toHaveCount(3);
    expect($property->projects->first())->toBeInstanceOf(Project::class);
});

// Test cascade deletes
test('deleting property deletes associated projects', function () {
    $property = Property::factory()->withProjects(3)->create();
    $projectIds = $property->projects->pluck('id');

    $property->delete();

    expect(Project::whereIn('id', $projectIds))->toHaveCount(0);
});
```

This comprehensive implementation provides a solid foundation for advanced Eloquent relationships in your property management system. The code is production-ready and follows Laravel best practices while addressing all the complex relationship scenarios you mentioned.