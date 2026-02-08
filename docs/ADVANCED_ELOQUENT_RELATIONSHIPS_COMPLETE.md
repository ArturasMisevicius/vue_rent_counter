# Advanced Eloquent Relationships - Complete Implementation

## Overview

This document provides a comprehensive implementation of advanced Eloquent relationships for a complex multi-tenant system with Users, Organizations, Projects, Tasks, Tags, Comments, and Files.

## Scenario Description

**Complex Relationship Requirements:**
1. Users belong to multiple Organizations with different roles and permissions
2. Projects can be personal (user-owned) or organizational (organization-owned)
3. Tasks can be assigned to multiple users with different responsibilities (assignee, reviewer, observer)
4. Tags are polymorphic across multiple models (Projects, Tasks, Users, Organizations)
5. Comments can be nested infinitely with moderation and threading
6. Files can be attached to any model with versioning and access control
7. Permissions are hierarchical and context-aware
8. Audit trails track all relationship changes

## Database Migrations

### 1. Enhanced Organization-User Pivot Table

```php
// database/migrations/create_organization_user_table.php
Schema::create('organization_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('role')->default('member'); // admin, manager, member, viewer
    $table->json('permissions')->nullable(); // specific permissions
    $table->timestamp('joined_at')->useCurrent();
    $table->timestamp('left_at')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('invited_by')->nullable()->constrained('users');
    $table->string('invitation_token')->nullable();
    $table->timestamp('invitation_sent_at')->nullable();
    $table->timestamp('invitation_accepted_at')->nullable();
    $table->timestamps();
    
    $table->unique(['organization_id', 'user_id']);
    $table->index(['user_id', 'is_active']);
    $table->index(['organization_id', 'role']);
});
```

### 2. Enhanced Projects Table with Polymorphic Ownership

```php
// database/migrations/create_projects_table.php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('organizations')->cascadeOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('type')->default('maintenance'); // maintenance, improvement, inspection
    $table->string('scope')->default('property'); // property, building, organization
    
    // Polymorphic ownership - can belong to User, Organization, Property, Building
    $table->morphs('projectable');
    
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('assigned_to')->nullable()->constrained('users');
    $table->string('status')->default('planning'); // planning, active, on_hold, completed, cancelled
    $table->string('priority')->default('medium'); // low, medium, high, urgent
    
    $table->date('start_date')->nullable();
    $table->date('due_date')->nullable();
    $table->timestamp('completed_at')->nullable();
    
    $table->decimal('budget', 10, 2)->nullable();
    $table->decimal('actual_cost', 10, 2)->default(0);
    
    $table->json('metadata')->nullable(); // custom fields, settings
    $table->json('settings')->nullable(); // project-specific settings
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['projectable_type', 'projectable_id']);
    $table->index(['tenant_id', 'status']);
    $table->index(['created_by', 'status']);
    $table->index(['due_date', 'status']);
});
```
### 3. Enhanced Task Assignments with Roles

```php
// database/migrations/create_task_assignments_table.php
Schema::create('task_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('task_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('role')->default('assignee'); // assignee, reviewer, observer, approver
    $table->timestamp('assigned_at')->useCurrent();
    $table->timestamp('completed_at')->nullable();
    $table->timestamp('reviewed_at')->nullable();
    $table->text('notes')->nullable();
    $table->json('completion_data')->nullable(); // custom completion data
    $table->decimal('hours_logged', 8, 2)->default(0);
    $table->string('status')->default('pending'); // pending, in_progress, completed, rejected
    $table->timestamps();
    
    $table->unique(['task_id', 'user_id', 'role']);
    $table->index(['user_id', 'status']);
    $table->index(['task_id', 'role']);
});
```

### 4. Polymorphic Taggables Table

```php
// database/migrations/create_taggables_table.php
Schema::create('taggables', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
    $table->morphs('taggable');
    $table->foreignId('tagged_by')->nullable()->constrained('users');
    $table->timestamp('tagged_at')->useCurrent();
    $table->json('metadata')->nullable(); // additional tag context
    $table->timestamps();
    
    $table->unique(['tag_id', 'taggable_type', 'taggable_id']);
    $table->index(['taggable_type', 'taggable_id']);
    $table->index(['tag_id', 'tagged_at']);
});
```

### 5. Enhanced Comments with Threading and Moderation

```php
// database/migrations/create_comments_table.php
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('organizations')->cascadeOnDelete();
    
    // Polymorphic commentable
    $table->morphs('commentable');
    
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
    
    $table->text('body');
    $table->boolean('is_internal')->default(false);
    $table->boolean('is_pinned')->default(false);
    
    // Threading support
    $table->integer('depth')->default(0);
    $table->string('path')->nullable(); // materialized path for efficient queries
    $table->integer('sort_order')->default(0);
    
    // Mentions and notifications
    $table->json('mentions')->nullable(); // user IDs mentioned
    
    // Resolution tracking
    $table->boolean('is_resolved')->default(false);
    $table->foreignId('resolved_by')->nullable()->constrained('users');
    $table->timestamp('resolved_at')->nullable();
    
    // Edit tracking
    $table->timestamp('edited_at')->nullable();
    
    // Moderation
    $table->string('moderation_status')->default('pending'); // pending, approved, rejected, flagged
    $table->foreignId('moderated_by')->nullable()->constrained('users');
    $table->timestamp('moderated_at')->nullable();
    $table->text('moderation_reason')->nullable();
    $table->json('moderation_flags')->nullable();
    
    // Spam and toxicity detection
    $table->integer('spam_score')->default(0);
    $table->integer('toxicity_score')->default(0);
    $table->integer('report_count')->default(0);
    $table->timestamp('last_reported_at')->nullable();
    
    // AI-powered insights
    $table->string('sentiment')->nullable(); // positive, negative, neutral
    $table->integer('technical_value')->default(0); // 0-100 technical relevance score
    $table->integer('relevance')->default(0); // 0-100 relevance to context
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['commentable_type', 'commentable_id']);
    $table->index(['parent_id', 'sort_order']);
    $table->index(['user_id', 'created_at']);
    $table->index(['moderation_status', 'created_at']);
    $table->index(['path']);
});
```

### 6. File Attachments with Versioning

```php
// database/migrations/create_attachments_table.php
Schema::create('attachments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('organizations')->cascadeOnDelete();
    
    // Polymorphic attachable
    $table->morphs('attachable');
    
    $table->foreignId('uploaded_by')->constrained('users');
    $table->string('name'); // original filename
    $table->string('filename'); // stored filename
    $table->string('path'); // storage path
    $table->string('disk')->default('local'); // storage disk
    $table->string('mime_type');
    $table->bigInteger('size'); // bytes
    $table->string('hash')->nullable(); // file hash for deduplication
    
    // Versioning
    $table->integer('version')->default(1);
    $table->foreignId('parent_id')->nullable()->constrained('attachments');
    $table->boolean('is_current_version')->default(true);
    
    // Access control
    $table->string('visibility')->default('private'); // public, private, restricted
    $table->json('access_permissions')->nullable(); // user/role specific permissions
    
    // Metadata
    $table->json('metadata')->nullable(); // EXIF, dimensions, etc.
    $table->text('description')->nullable();
    $table->json('tags')->nullable();
    
    // Processing status for images/documents
    $table->string('processing_status')->default('pending'); // pending, processing, completed, failed
    $table->json('thumbnails')->nullable(); // generated thumbnail paths
    
    // Virus scanning
    $table->string('scan_status')->default('pending'); // pending, clean, infected, failed
    $table->timestamp('scanned_at')->nullable();
    
    $table->timestamps();
    $table->softDeletes();
    
    $table->index(['attachable_type', 'attachable_id']);
    $table->index(['uploaded_by', 'created_at']);
    $table->index(['hash']); // for deduplication
    $table->index(['parent_id', 'version']);
});
```

## Enhanced Model Definitions

### 1. Enhanced User Model Relationships

```php
// app/Models/User.php (additional relationships)
class User extends Authenticatable implements FilamentUser
{
    // ... existing code ...

    /**
     * Organizations this user belongs to with roles
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->using(OrganizationUser::class)
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
            ->using(OrganizationUser::class)
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
     * Personal projects (polymorphic)
     */
    public function personalProjects(): MorphMany
    {
        return $this->morphMany(Project::class, 'projectable');
    }

    /**
     * Tasks assigned to this user with roles
     */
    public function taskAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments')
            ->using(TaskAssignment::class)
            ->withPivot(['role', 'assigned_at', 'completed_at', 'notes', 'hours_logged', 'status'])
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
     * Comments created by this user
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Files uploaded by this user
     */
    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
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
            ->orWhere('assigned_to', $this->id)
            ->orWhere(function ($query) {
                $query->where('projectable_type', User::class)
                      ->where('projectable_id', $this->id);
            });
    }

    /**
     * Get tasks with specific role
     */
    public function getTasksByRole(string $role): Collection
    {
        return $this->taskAssignments()
            ->wherePivot('role', $role)
            ->wherePivot('status', '!=', 'completed')
            ->get();
    }

    /**
     * Get overdue tasks assigned to user
     */
    public function overdueTasks(): Builder
    {
        return $this->assignedTasks()
            ->join('tasks', 'task_assignments.task_id', '=', 'tasks.id')
            ->where('tasks.due_date', '<', now())
            ->whereNotIn('tasks.status', ['completed', 'cancelled']);
    }
}
```
### 2. Enhanced Organization Model

```php
// app/Models/Organization.php (additional relationships)
class Organization extends Model
{
    // ... existing code ...

    /**
     * Users that belong to this organization with roles
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'permissions', 'joined_at', 'left_at', 'is_active', 'invited_by'])
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    /**
     * All user memberships including inactive
     */
    public function allMemberships(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'permissions', 'joined_at', 'left_at', 'is_active', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * Get projects for this organization (polymorphic)
     */
    public function projects(): MorphMany
    {
        return $this->morphMany(Project::class, 'projectable');
    }

    /**
     * Get organization-wide projects
     */
    public function organizationProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'tenant_id');
    }

    /**
     * Get all tasks across organization projects
     */
    public function allTasks(): HasManyThrough
    {
        return $this->hasManyThrough(Task::class, Project::class, 'tenant_id', 'project_id');
    }

    /**
     * Get members with specific role
     */
    public function getMembersByRole(string $role): Collection
    {
        return $this->members()->wherePivot('role', $role)->get();
    }

    /**
     * Get administrators
     */
    public function administrators(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'admin');
    }

    /**
     * Get managers
     */
    public function managers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'manager');
    }

    /**
     * Add member with role
     */
    public function addMember(User $user, string $role = 'member', array $permissions = []): void
    {
        $this->members()->attach($user->id, [
            'role' => $role,
            'permissions' => $permissions,
            'joined_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Update member role
     */
    public function updateMemberRole(User $user, string $role): void
    {
        $this->members()->updateExistingPivot($user->id, ['role' => $role]);
    }

    /**
     * Remove member
     */
    public function removeMember(User $user): void
    {
        $this->members()->updateExistingPivot($user->id, [
            'is_active' => false,
            'left_at' => now(),
        ]);
    }

    /**
     * Get organization statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_members' => $this->members()->count(),
            'active_projects' => $this->organizationProjects()->where('status', 'active')->count(),
            'completed_projects' => $this->organizationProjects()->where('status', 'completed')->count(),
            'pending_tasks' => $this->allTasks()->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'overdue_tasks' => $this->allTasks()->where('due_date', '<', now())->whereNotIn('status', ['completed', 'cancelled'])->count(),
        ];
    }
}
```

### 3. Enhanced Project Model with Polymorphic Relationships

```php
// app/Models/Project.php (enhanced version)
class Project extends Model
{
    use HasFactory, BelongsToTenant, HasTags, HasComments, HasAttachments;

    protected $fillable = [
        'tenant_id', 'name', 'description', 'type', 'scope',
        'projectable_type', 'projectable_id', 'created_by', 'assigned_to',
        'status', 'priority', 'start_date', 'due_date', 'completed_at',
        'budget', 'actual_cost', 'metadata', 'settings'
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'metadata' => 'array',
        'settings' => 'array',
    ];

    /**
     * Get the parent projectable model (User, Organization, Property, Building)
     */
    public function projectable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the organization this project belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
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
     * Get project watchers (users who want notifications)
     */
    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_watchers')
            ->withTimestamps();
    }

    /**
     * Get project collaborators (users with specific permissions)
     */
    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_collaborators')
            ->withPivot(['role', 'permissions', 'added_at'])
            ->withTimestamps();
    }

    /**
     * Scope: Projects for specific model
     */
    public function scopeForModel($query, Model $model)
    {
        return $query->where('projectable_type', get_class($model))
                    ->where('projectable_id', $model->id);
    }

    /**
     * Scope: Personal projects (owned by users)
     */
    public function scopePersonal($query)
    {
        return $query->where('projectable_type', User::class);
    }

    /**
     * Scope: Organizational projects
     */
    public function scopeOrganizational($query)
    {
        return $query->where('projectable_type', Organization::class);
    }

    /**
     * Get project progress based on tasks
     */
    public function getProgress(): array
    {
        $totalTasks = $this->tasks()->count();
        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        $inProgressTasks = $this->tasks()->where('status', 'in_progress')->count();
        $pendingTasks = $this->tasks()->where('status', 'pending')->count();

        return [
            'total' => $totalTasks,
            'completed' => $completedTasks,
            'in_progress' => $inProgressTasks,
            'pending' => $pendingTasks,
            'completion_percentage' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
        ];
    }

    /**
     * Get budget analysis
     */
    public function getBudgetAnalysis(): array
    {
        $utilization = $this->budget > 0 ? ($this->actual_cost / $this->budget) * 100 : 0;
        $remaining = $this->budget - $this->actual_cost;

        return [
            'budget' => $this->budget,
            'actual_cost' => $this->actual_cost,
            'remaining' => $remaining,
            'utilization_percentage' => round($utilization, 2),
            'is_over_budget' => $this->actual_cost > $this->budget,
            'variance' => $this->actual_cost - $this->budget,
        ];
    }

    /**
     * Add collaborator with specific role
     */
    public function addCollaborator(User $user, string $role = 'contributor', array $permissions = []): void
    {
        $this->collaborators()->attach($user->id, [
            'role' => $role,
            'permissions' => $permissions,
            'added_at' => now(),
        ]);
    }

    /**
     * Check if user can access project
     */
    public function userCanAccess(User $user): bool
    {
        // Creator always has access
        if ($this->created_by === $user->id) {
            return true;
        }

        // Assignee has access
        if ($this->assigned_to === $user->id) {
            return true;
        }

        // Check if user is a collaborator
        if ($this->collaborators()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check organization membership
        if ($this->projectable_type === Organization::class) {
            return $user->organizations()->where('organization_id', $this->projectable_id)->exists();
        }

        // Check if it's user's personal project
        if ($this->projectable_type === User::class && $this->projectable_id === $user->id) {
            return true;
        }

        return false;
    }
}
```
### 4. Enhanced Task Model with Complex Assignments

```php
// app/Models/Task.php (enhanced version)
class Task extends Model
{
    use HasFactory, BelongsToTenant, HasTags, HasComments, HasAttachments;

    protected $fillable = [
        'tenant_id', 'project_id', 'title', 'description', 'status', 'priority',
        'created_by', 'due_date', 'completed_at', 'estimated_hours', 'actual_hours',
        'checklist', 'dependencies', 'blocking_reason'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'checklist' => 'array',
        'dependencies' => 'array',
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
            ->withPivot(['role', 'assigned_at', 'completed_at', 'notes', 'hours_logged', 'status'])
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
     * Get approvers
     */
    public function approvers(): BelongsToMany
    {
        return $this->assignedUsers()->wherePivot('role', 'approver');
    }

    /**
     * Task dependencies (tasks that must be completed first)
     */
    public function dependsOn(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
            ->withPivot(['dependency_type', 'created_at']);
    }

    /**
     * Tasks that depend on this task
     */
    public function dependentTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
            ->withPivot(['dependency_type', 'created_at']);
    }

    /**
     * Subtasks
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Parent task
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Time tracking entries
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
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
            'status' => 'pending',
        ]);
    }

    /**
     * Mark task as completed by user
     */
    public function markCompletedBy(User $user, ?string $notes = null): void
    {
        $this->assignedUsers()->updateExistingPivot($user->id, [
            'completed_at' => now(),
            'status' => 'completed',
            'notes' => $notes,
        ]);

        // Check if all assignees completed
        $this->checkOverallCompletion();
    }

    /**
     * Check if all assignees have completed their work
     */
    protected function checkOverallCompletion(): void
    {
        $totalAssignees = $this->assignees()->count();
        $completedAssignees = $this->assignees()
            ->wherePivot('status', 'completed')
            ->count();

        if ($totalAssignees > 0 && $totalAssignees === $completedAssignees) {
            // Check if reviewers need to approve
            $totalReviewers = $this->reviewers()->count();
            if ($totalReviewers > 0) {
                $this->update(['status' => 'under_review']);
            } else {
                $this->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }
        }
    }

    /**
     * Add dependency
     */
    public function addDependency(Task $dependsOnTask, string $type = 'finish_to_start'): void
    {
        $this->dependsOn()->attach($dependsOnTask->id, [
            'dependency_type' => $type,
            'created_at' => now(),
        ]);
    }

    /**
     * Check if task can be started (all dependencies completed)
     */
    public function canStart(): bool
    {
        return $this->dependsOn()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count() === 0;
    }

    /**
     * Get blocking dependencies
     */
    public function getBlockingDependencies(): Collection
    {
        return $this->dependsOn()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();
    }

    /**
     * Calculate total logged hours
     */
    public function getTotalLoggedHours(): float
    {
        return $this->assignedUsers()->sum('task_assignments.hours_logged') +
               $this->timeEntries()->sum('hours');
    }

    /**
     * Get task efficiency (estimated vs actual)
     */
    public function getEfficiency(): ?float
    {
        if (!$this->estimated_hours) {
            return null;
        }

        $actualHours = $this->getTotalLoggedHours();
        return $actualHours > 0 ? ($this->estimated_hours / $actualHours) * 100 : null;
    }

    /**
     * Scope: Tasks assigned to user with specific role
     */
    public function scopeAssignedToUserWithRole($query, User $user, string $role)
    {
        return $query->whereHas('assignedUsers', function ($q) use ($user, $role) {
            $q->where('user_id', $user->id)->where('role', $role);
        });
    }

    /**
     * Scope: Tasks ready to start (no blocking dependencies)
     */
    public function scopeReadyToStart($query)
    {
        return $query->where('status', 'pending')
            ->whereDoesntHave('dependsOn', function ($q) {
                $q->whereNotIn('status', ['completed', 'cancelled']);
            });
    }

    /**
     * Scope: Blocked tasks
     */
    public function scopeBlocked($query)
    {
        return $query->whereHas('dependsOn', function ($q) {
            $q->whereNotIn('status', ['completed', 'cancelled']);
        });
    }
}
```

### 5. Enhanced Tag Model with Polymorphic Relationships

```php
// app/Models/Tag.php (enhanced version)
class Tag extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'color', 'description', 'usage_count',
        'type', 'is_system', 'metadata'
    ];

    protected $casts = [
        'usage_count' => 'integer',
        'is_system' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * Get all projects with this tag
     */
    public function projects(): MorphToMany
    {
        return $this->morphedByMany(Project::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Get all tasks with this tag
     */
    public function tasks(): MorphToMany
    {
        return $this->morphedByMany(Task::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Get all users with this tag
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Get all organizations with this tag
     */
    public function organizations(): MorphToMany
    {
        return $this->morphedByMany(Organization::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Get all properties with this tag
     */
    public function properties(): MorphToMany
    {
        return $this->morphedByMany(Property::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Get all comments with this tag
     */
    public function comments(): MorphToMany
    {
        return $this->morphedByMany(Comment::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Update usage count efficiently
     */
    public function updateUsageCount(): void
    {
        $count = DB::table('taggables')
            ->where('tag_id', $this->id)
            ->count();

        $this->usage_count = $count;
        $this->saveQuietly();
    }

    /**
     * Get related tags (tags that appear together frequently)
     */
    public function getRelatedTags(int $limit = 10): Collection
    {
        return static::select('tags.*', DB::raw('COUNT(*) as co_occurrence'))
            ->join('taggables as t1', 'tags.id', '=', 't1.tag_id')
            ->join('taggables as t2', function ($join) {
                $join->on('t1.taggable_type', '=', 't2.taggable_type')
                     ->on('t1.taggable_id', '=', 't2.taggable_id');
            })
            ->where('t2.tag_id', $this->id)
            ->where('tags.id', '!=', $this->id)
            ->groupBy('tags.id')
            ->orderBy('co_occurrence', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Scope: Popular tags
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Scope: Tags by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: System tags
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: User-created tags
     */
    public function scopeUserCreated($query)
    {
        return $query->where('is_system', false);
    }
}
```
### 6. Enhanced Comment Model with Threading

```php
// app/Models/Comment.php (enhanced version)
class Comment extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes, HasTags;

    protected $fillable = [
        'tenant_id', 'commentable_type', 'commentable_id', 'user_id', 'parent_id',
        'body', 'is_internal', 'is_pinned', 'depth', 'path', 'sort_order',
        'mentions', 'is_resolved', 'resolved_by', 'resolved_at', 'edited_at',
        'moderation_status', 'moderated_by', 'moderated_at', 'moderation_reason',
        'spam_score', 'toxicity_score', 'report_count', 'last_reported_at',
        'sentiment', 'technical_value', 'relevance'
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_pinned' => 'boolean',
        'mentions' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'edited_at' => 'datetime',
        'moderated_at' => 'datetime',
        'moderation_flags' => 'array',
        'last_reported_at' => 'datetime',
        'spam_score' => 'integer',
        'toxicity_score' => 'integer',
        'report_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Comment $comment) {
            if ($comment->parent_id) {
                $parent = static::find($comment->parent_id);
                $comment->depth = $parent->depth + 1;
                $comment->path = $parent->path . '.' . $parent->id;
            } else {
                $comment->depth = 0;
                $comment->path = '';
            }
        });
    }

    /**
     * Get the parent commentable model
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who moderated the comment
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Get the user who resolved the comment
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the parent comment
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get direct replies
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get all descendants (recursive)
     */
    public function descendants(): HasMany
    {
        return $this->replies()->with('descendants');
    }

    /**
     * Get all ancestors
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;
        
        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }
        
        return $ancestors;
    }

    /**
     * Get thread root
     */
    public function getRoot(): Comment
    {
        if (!$this->parent_id) {
            return $this;
        }
        
        return $this->ancestors()->first() ?? $this;
    }

    /**
     * Get mentioned users
     */
    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comment_mentions')
            ->withTimestamps();
    }

    /**
     * Get comment reactions
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    /**
     * Get comment reports
     */
    public function reports(): HasMany
    {
        return $this->hasMany(CommentReport::class);
    }

    /**
     * Add reaction
     */
    public function addReaction(User $user, string $type): void
    {
        $this->reactions()->updateOrCreate(
            ['user_id' => $user->id],
            ['type' => $type, 'created_at' => now()]
        );
    }

    /**
     * Remove reaction
     */
    public function removeReaction(User $user): void
    {
        $this->reactions()->where('user_id', $user->id)->delete();
    }

    /**
     * Get reaction counts
     */
    public function getReactionCounts(): array
    {
        return $this->reactions()
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Mention users in comment
     */
    public function mentionUsers(array $userIds): void
    {
        $this->mentionedUsers()->sync($userIds);
        $this->mentions = $userIds;
        $this->save();
    }

    /**
     * Mark as resolved
     */
    public function resolve(User $user, ?string $reason = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'moderation_reason' => $reason,
        ]);
    }

    /**
     * Mark as edited
     */
    public function markAsEdited(): void
    {
        $this->edited_at = now();
        $this->save();
    }

    /**
     * Report comment
     */
    public function reportByUser(User $user, string $reason): void
    {
        $this->reports()->create([
            'reported_by' => $user->id,
            'reason' => $reason,
            'created_at' => now(),
        ]);

        $this->increment('report_count');
        $this->update(['last_reported_at' => now()]);

        // Auto-flag if too many reports
        if ($this->report_count >= 3 && $this->moderation_status === 'pending') {
            $this->update(['moderation_status' => 'flagged']);
        }
    }

    /**
     * Scope: Thread comments (including replies)
     */
    public function scopeInThread($query, Comment $rootComment)
    {
        return $query->where(function ($q) use ($rootComment) {
            $q->where('id', $rootComment->id)
              ->orWhere('path', 'like', $rootComment->path . '.%');
        });
    }

    /**
     * Scope: Top-level comments
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Approved comments
     */
    public function scopeApproved($query)
    {
        return $query->where('moderation_status', 'approved');
    }

    /**
     * Scope: Comments needing moderation
     */
    public function scopeNeedsModeration($query)
    {
        return $query->whereIn('moderation_status', ['pending', 'flagged']);
    }

    /**
     * Check if comment is edited
     */
    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    /**
     * Check if comment is a reply
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Check if comment needs moderation
     */
    public function needsModeration(): bool
    {
        return in_array($this->moderation_status, ['pending', 'flagged']);
    }

    /**
     * Get comment depth in thread
     */
    public function getThreadDepth(): int
    {
        return $this->depth;
    }

    /**
     * Get comment position in thread
     */
    public function getThreadPosition(): string
    {
        return $this->path ? $this->path . '.' . $this->id : (string) $this->id;
    }
}
```

### 7. Attachment Model with Versioning

```php
// app/Models/Attachment.php
class Attachment extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'attachable_type', 'attachable_id', 'uploaded_by',
        'name', 'filename', 'path', 'disk', 'mime_type', 'size', 'hash',
        'version', 'parent_id', 'is_current_version', 'visibility',
        'access_permissions', 'metadata', 'description', 'tags',
        'processing_status', 'thumbnails', 'scan_status', 'scanned_at'
    ];

    protected $casts = [
        'size' => 'integer',
        'version' => 'integer',
        'is_current_version' => 'boolean',
        'access_permissions' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'thumbnails' => 'array',
        'scanned_at' => 'datetime',
    ];

    /**
     * Get the parent attachable model
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this file
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the parent attachment (for versions)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'parent_id');
    }

    /**
     * Get all versions of this attachment
     */
    public function versions(): HasMany
    {
        return $this->hasMany(Attachment::class, 'parent_id')
            ->orderBy('version', 'desc');
    }

    /**
     * Get the latest version
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(Attachment::class, 'parent_id')
            ->where('is_current_version', true);
    }

    /**
     * Get file access logs
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(FileAccessLog::class);
    }

    /**
     * Create new version
     */
    public function createVersion(array $attributes): Attachment
    {
        // Mark current version as not current
        $this->update(['is_current_version' => false]);
        
        // Create new version
        $newVersion = static::create(array_merge($attributes, [
            'parent_id' => $this->parent_id ?? $this->id,
            'version' => $this->getNextVersionNumber(),
            'is_current_version' => true,
        ]));

        return $newVersion;
    }

    /**
     * Get next version number
     */
    protected function getNextVersionNumber(): int
    {
        $parentId = $this->parent_id ?? $this->id;
        
        return static::where('parent_id', $parentId)
            ->orWhere('id', $parentId)
            ->max('version') + 1;
    }

    /**
     * Check if user can access file
     */
    public function userCanAccess(User $user): bool
    {
        // Uploader always has access
        if ($this->uploaded_by === $user->id) {
            return true;
        }

        // Public files are accessible to all
        if ($this->visibility === 'public') {
            return true;
        }

        // Check specific permissions
        if ($this->access_permissions) {
            $permissions = $this->access_permissions;
            
            // Check user-specific permissions
            if (isset($permissions['users']) && in_array($user->id, $permissions['users'])) {
                return true;
            }
            
            // Check role-based permissions
            if (isset($permissions['roles']) && in_array($user->role->value, $permissions['roles'])) {
                return true;
            }
        }

        // Check if user has access to the parent model
        return $this->attachable && method_exists($this->attachable, 'userCanAccess') 
            ? $this->attachable->userCanAccess($user) 
            : false;
    }

    /**
     * Log file access
     */
    public function logAccess(User $user, string $action = 'view'): void
    {
        $this->accessLogs()->create([
            'user_id' => $user->id,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'accessed_at' => now(),
        ]);
    }

    /**
     * Get file URL
     */
    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl(string $size = 'medium'): ?string
    {
        if (!$this->thumbnails || !isset($this->thumbnails[$size])) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->thumbnails[$size]);
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is a document
     */
    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Get human-readable file size
     */
    public function getHumanReadableSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope: Current versions only
     */
    public function scopeCurrentVersions($query)
    {
        return $query->where('is_current_version', true);
    }

    /**
     * Scope: Images only
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope: Documents only
     */
    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
```
## Pivot Model Classes

### 1. Enhanced OrganizationUser Pivot

```php
// app/Models/OrganizationUser.php (enhanced version)
class OrganizationUser extends Pivot
{
    protected $table = 'organization_user';
    public $incrementing = true;

    protected $fillable = [
        'organization_id', 'user_id', 'role', 'permissions', 'joined_at',
        'left_at', 'is_active', 'invited_by', 'invitation_token',
        'invitation_sent_at', 'invitation_accepted_at'
    ];

    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_active' => 'boolean',
        'invitation_sent_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        static::creating(function (OrganizationUser $pivot) {
            if (!$pivot->joined_at) {
                $pivot->joined_at = now();
            }
        });
    }

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
     * Get membership activities
     */
    public function activities(): HasMany
    {
        return $this->hasMany(MembershipActivity::class, 'membership_id');
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $userPermissions = $this->permissions ?? [];
        return !empty(array_intersect($permissions, $userPermissions));
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = $this->permissions ?? [];
        return empty(array_diff($permissions, $userPermissions));
    }

    /**
     * Add permission
     */
    public function grantPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->permissions = $permissions;
            $this->save();
            
            $this->logActivity('permission_granted', ['permission' => $permission]);
        }
    }

    /**
     * Remove permission
     */
    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $this->permissions = array_values(array_diff($permissions, [$permission]));
        $this->save();
        
        $this->logActivity('permission_revoked', ['permission' => $permission]);
    }

    /**
     * Set multiple permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $oldPermissions = $this->permissions ?? [];
        $this->permissions = array_unique($permissions);
        $this->save();
        
        $granted = array_diff($permissions, $oldPermissions);
        $revoked = array_diff($oldPermissions, $permissions);
        
        if (!empty($granted) || !empty($revoked)) {
            $this->logActivity('permissions_synced', [
                'granted' => $granted,
                'revoked' => $revoked,
            ]);
        }
    }

    /**
     * Change role
     */
    public function changeRole(string $newRole): void
    {
        $oldRole = $this->role;
        $this->role = $newRole;
        $this->save();
        
        $this->logActivity('role_changed', [
            'old_role' => $oldRole,
            'new_role' => $newRole,
        ]);
    }

    /**
     * Deactivate membership
     */
    public function deactivate(?string $reason = null): void
    {
        $this->update([
            'is_active' => false,
            'left_at' => now(),
        ]);
        
        $this->logActivity('membership_deactivated', ['reason' => $reason]);
    }

    /**
     * Reactivate membership
     */
    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'left_at' => null,
        ]);
        
        $this->logActivity('membership_reactivated');
    }

    /**
     * Accept invitation
     */
    public function acceptInvitation(): void
    {
        $this->update([
            'invitation_accepted_at' => now(),
            'is_active' => true,
        ]);
        
        $this->logActivity('invitation_accepted');
    }

    /**
     * Log membership activity
     */
    protected function logActivity(string $action, array $data = []): void
    {
        $this->activities()->create([
            'action' => $action,
            'data' => $data,
            'performed_at' => now(),
            'performed_by' => auth()->id(),
        ]);
    }

    /**
     * Get membership duration in days
     */
    public function getMembershipDuration(): int
    {
        $endDate = $this->left_at ?? now();
        return $this->joined_at->diffInDays($endDate);
    }

    /**
     * Check if membership is active
     */
    public function isActiveMembership(): bool
    {
        return $this->is_active && $this->left_at === null;
    }

    /**
     * Check if invitation is pending
     */
    public function isInvitationPending(): bool
    {
        return $this->invitation_sent_at && !$this->invitation_accepted_at;
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'member' => 'Member',
            'viewer' => 'Viewer',
            default => ucfirst($this->role),
        };
    }

    /**
     * Get default permissions for role
     */
    public static function getDefaultPermissionsForRole(string $role): array
    {
        return match($role) {
            'admin' => [
                'manage_members', 'manage_projects', 'manage_tasks',
                'view_reports', 'manage_settings', 'delete_organization'
            ],
            'manager' => [
                'manage_projects', 'manage_tasks', 'view_reports', 'invite_members'
            ],
            'member' => [
                'create_projects', 'manage_own_tasks', 'comment_on_projects'
            ],
            'viewer' => [
                'view_projects', 'view_tasks', 'comment_on_projects'
            ],
            default => [],
        };
    }
}
```

### 2. Enhanced TaskAssignment Pivot

```php
// app/Models/TaskAssignment.php (enhanced version)
class TaskAssignment extends Pivot
{
    protected $table = 'task_assignments';
    public $incrementing = true;

    protected $fillable = [
        'task_id', 'user_id', 'role', 'assigned_at', 'completed_at',
        'reviewed_at', 'notes', 'completion_data', 'hours_logged', 'status'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completion_data' => 'array',
        'hours_logged' => 'decimal:2',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        static::creating(function (TaskAssignment $assignment) {
            if (!$assignment->assigned_at) {
                $assignment->assigned_at = now();
            }
        });

        static::updating(function (TaskAssignment $assignment) {
            // Auto-set reviewed_at when status changes to completed for reviewers
            if ($assignment->role === 'reviewer' && 
                $assignment->status === 'completed' && 
                !$assignment->reviewed_at) {
                $assignment->reviewed_at = now();
            }
        });
    }

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
     * Get time tracking entries for this assignment
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'assignment_id');
    }

    /**
     * Get assignment activities/logs
     */
    public function activities(): HasMany
    {
        return $this->hasMany(AssignmentActivity::class, 'assignment_id');
    }

    /**
     * Mark as completed
     */
    public function markCompleted(?string $notes = null, array $completionData = []): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $notes ?? $this->notes,
            'completion_data' => array_merge($this->completion_data ?? [], $completionData),
        ]);

        $this->logActivity('completed', ['notes' => $notes, 'completion_data' => $completionData]);
        
        // Trigger task completion check
        $this->task->checkOverallCompletion();
    }

    /**
     * Mark as in progress
     */
    public function markInProgress(?string $notes = null): void
    {
        $this->update([
            'status' => 'in_progress',
            'notes' => $notes ?? $this->notes,
        ]);

        $this->logActivity('started', ['notes' => $notes]);
    }

    /**
     * Add time entry
     */
    public function logTime(float $hours, string $description = '', array $metadata = []): TimeEntry
    {
        $timeEntry = $this->timeEntries()->create([
            'user_id' => $this->user_id,
            'task_id' => $this->task_id,
            'hours' => $hours,
            'description' => $description,
            'metadata' => $metadata,
            'logged_at' => now(),
        ]);

        // Update total hours logged
        $this->hours_logged = $this->timeEntries()->sum('hours');
        $this->save();

        return $timeEntry;
    }

    /**
     * Get total time logged
     */
    public function getTotalTimeLogged(): float
    {
        return $this->timeEntries()->sum('hours');
    }

    /**
     * Get assignment duration
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
     * Check if assignment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get role-specific requirements
     */
    public function getRoleRequirements(): array
    {
        return match($this->role) {
            'assignee' => [
                'must_complete_work' => true,
                'can_log_time' => true,
                'needs_approval' => false,
            ],
            'reviewer' => [
                'must_review_work' => true,
                'can_approve_reject' => true,
                'needs_completion_first' => true,
            ],
            'approver' => [
                'must_approve' => true,
                'can_approve_reject' => true,
                'needs_review_first' => true,
            ],
            'observer' => [
                'receives_notifications' => true,
                'can_comment' => true,
                'no_action_required' => true,
            ],
            default => [],
        };
    }

    /**
     * Check if user can perform action based on role
     */
    public function canPerformAction(string $action): bool
    {
        $requirements = $this->getRoleRequirements();
        
        return match($action) {
            'complete' => $this->role === 'assignee' && $this->status !== 'completed',
            'review' => $this->role === 'reviewer' && $this->task->isCompletedByAssignees(),
            'approve' => $this->role === 'approver' && $this->task->isReviewedByReviewers(),
            'log_time' => in_array($this->role, ['assignee', 'reviewer']),
            'comment' => true, // All roles can comment
            default => false,
        };
    }

    /**
     * Log assignment activity
     */
    protected function logActivity(string $action, array $data = []): void
    {
        $this->activities()->create([
            'action' => $action,
            'data' => $data,
            'performed_at' => now(),
            'performed_by' => auth()->id(),
        ]);
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            'assignee' => 'Assignee',
            'reviewer' => 'Reviewer',
            'approver' => 'Approver',
            'observer' => 'Observer',
            default => ucfirst($this->role),
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'on_hold' => 'On Hold',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope: Active assignments
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    /**
     * Scope: Completed assignments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Overdue assignments
     */
    public function scopeOverdue($query)
    {
        return $query->whereHas('task', function ($q) {
            $q->where('due_date', '<', now());
        })->whereNotIn('status', ['completed', 'rejected']);
    }
}
```
## Traits for Reusable Relationships

### 1. HasTags Trait

```php
// app/Traits/HasTags.php
trait HasTags
{
    /**
     * Get all tags for this model
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withTimestamps()
            ->withPivot('tagged_by');
    }

    /**
     * Tag the model with given tags
     */
    public function tag(array|string $tags, ?User $taggedBy = null): void
    {
        $tags = is_string($tags) ? [$tags] : $tags;
        $taggedBy = $taggedBy ?? auth()->user();
        
        foreach ($tags as $tagName) {
            $tag = Tag::firstOrCreate(
                ['name' => $tagName, 'tenant_id' => $this->tenant_id ?? null],
                ['slug' => Str::slug($tagName)]
            );
            
            $this->tags()->syncWithoutDetaching([
                $tag->id => ['tagged_by' => $taggedBy?->id]
            ]);
        }
    }

    /**
     * Untag the model
     */
    public function untag(array|string $tags): void
    {
        $tags = is_string($tags) ? [$tags] : $tags;
        
        $tagIds = Tag::whereIn('name', $tags)
            ->where('tenant_id', $this->tenant_id ?? null)
            ->pluck('id');
            
        $this->tags()->detach($tagIds);
    }

    /**
     * Sync tags
     */
    public function syncTags(array $tags, ?User $taggedBy = null): void
    {
        $taggedBy = $taggedBy ?? auth()->user();
        $tagIds = [];
        
        foreach ($tags as $tagName) {
            $tag = Tag::firstOrCreate(
                ['name' => $tagName, 'tenant_id' => $this->tenant_id ?? null],
                ['slug' => Str::slug($tagName)]
            );
            
            $tagIds[$tag->id] = ['tagged_by' => $taggedBy?->id];
        }
        
        $this->tags()->sync($tagIds);
    }

    /**
     * Get tag names
     */
    public function getTagNames(): array
    {
        return $this->tags->pluck('name')->toArray();
    }

    /**
     * Check if model has tag
     */
    public function hasTag(string $tagName): bool
    {
        return $this->tags()->where('name', $tagName)->exists();
    }

    /**
     * Check if model has any of the given tags
     */
    public function hasAnyTag(array $tags): bool
    {
        return $this->tags()->whereIn('name', $tags)->exists();
    }

    /**
     * Check if model has all of the given tags
     */
    public function hasAllTags(array $tags): bool
    {
        return $this->tags()->whereIn('name', $tags)->count() === count($tags);
    }

    /**
     * Scope: Models with any of the given tags
     */
    public function scopeWithAnyTags($query, array $tags)
    {
        return $query->whereHas('tags', function ($q) use ($tags) {
            $q->whereIn('name', $tags);
        });
    }

    /**
     * Scope: Models with all of the given tags
     */
    public function scopeWithAllTags($query, array $tags)
    {
        return $query->whereHas('tags', function ($q) use ($tags) {
            $q->whereIn('name', $tags);
        }, '=', count($tags));
    }

    /**
     * Scope: Models without any of the given tags
     */
    public function scopeWithoutTags($query, array $tags)
    {
        return $query->whereDoesntHave('tags', function ($q) use ($tags) {
            $q->whereIn('name', $tags);
        });
    }
}
```

### 2. HasComments Trait

```php
// app/Traits/HasComments.php
trait HasComments
{
    /**
     * Get all comments for this model
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get top-level comments (no parent)
     */
    public function topLevelComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    /**
     * Get approved comments
     */
    public function approvedComments(): MorphMany
    {
        return $this->comments()->where('moderation_status', 'approved');
    }

    /**
     * Get internal comments
     */
    public function internalComments(): MorphMany
    {
        return $this->comments()->where('is_internal', true);
    }

    /**
     * Get public comments
     */
    public function publicComments(): MorphMany
    {
        return $this->comments()->where('is_internal', false);
    }

    /**
     * Add a comment
     */
    public function comment(string $body, ?User $user = null, array $options = []): Comment
    {
        $user = $user ?? auth()->user();
        
        return $this->comments()->create(array_merge([
            'user_id' => $user->id,
            'body' => $body,
            'tenant_id' => $this->tenant_id ?? null,
            'is_internal' => false,
            'moderation_status' => 'pending',
        ], $options));
    }

    /**
     * Add an internal comment
     */
    public function internalComment(string $body, ?User $user = null): Comment
    {
        return $this->comment($body, $user, ['is_internal' => true]);
    }

    /**
     * Get comment count
     */
    public function getCommentCount(): int
    {
        return $this->comments()->count();
    }

    /**
     * Get approved comment count
     */
    public function getApprovedCommentCount(): int
    {
        return $this->approvedComments()->count();
    }

    /**
     * Get latest comment
     */
    public function getLatestComment(): ?Comment
    {
        return $this->comments()->latest()->first();
    }

    /**
     * Check if model has comments
     */
    public function hasComments(): bool
    {
        return $this->comments()->exists();
    }

    /**
     * Get comment threads (top-level comments with replies)
     */
    public function getCommentThreads(): Collection
    {
        return $this->topLevelComments()
            ->with(['replies' => function ($query) {
                $query->with('replies')->orderBy('created_at', 'asc');
            }])
            ->get();
    }
}
```

### 3. HasAttachments Trait

```php
// app/Traits/HasAttachments.php
trait HasAttachments
{
    /**
     * Get all attachments for this model
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get current version attachments only
     */
    public function currentAttachments(): MorphMany
    {
        return $this->attachments()->where('is_current_version', true);
    }

    /**
     * Get images only
     */
    public function images(): MorphMany
    {
        return $this->attachments()->where('mime_type', 'like', 'image/%');
    }

    /**
     * Get documents only
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
        ?User $uploadedBy = null, 
        array $options = []
    ): Attachment {
        $uploadedBy = $uploadedBy ?? auth()->user();
        $filename = $this->generateUniqueFilename($file);
        $path = $file->storeAs('attachments', $filename);
        
        return $this->attachments()->create(array_merge([
            'uploaded_by' => $uploadedBy->id,
            'name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'hash' => hash_file('sha256', $file->getRealPath()),
            'tenant_id' => $this->tenant_id ?? null,
        ], $options));
    }

    /**
     * Attach multiple files
     */
    public function attachFiles(array $files, ?User $uploadedBy = null): Collection
    {
        $attachments = collect();
        
        foreach ($files as $file) {
            $attachments->push($this->attachFile($file, $uploadedBy));
        }
        
        return $attachments;
    }

    /**
     * Get total attachment size
     */
    public function getTotalAttachmentSize(): int
    {
        return $this->attachments()->sum('size');
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCount(): int
    {
        return $this->attachments()->count();
    }

    /**
     * Check if model has attachments
     */
    public function hasAttachments(): bool
    {
        return $this->attachments()->exists();
    }

    /**
     * Generate unique filename
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . '.' . $extension;
    }

    /**
     * Get attachments by type
     */
    public function getAttachmentsByType(string $type): Collection
    {
        return match($type) {
            'images' => $this->images,
            'documents' => $this->documents,
            'current' => $this->currentAttachments,
            default => $this->attachments,
        };
    }
}
```

## Complex Querying Examples

### 1. Cross-Organization Project Queries

```php
// Get all projects for a user across all organizations
$userProjects = Project::whereHas('organization.members', function ($query) use ($user) {
    $query->where('user_id', $user->id)->where('is_active', true);
})
->orWhere('created_by', $user->id)
->orWhere('assigned_to', $user->id)
->orWhere(function ($query) use ($user) {
    $query->where('projectable_type', User::class)
          ->where('projectable_id', $user->id);
})
->with(['tasks.assignedUsers', 'tags', 'comments.user'])
->get();

// Get projects with overdue tasks assigned to specific user
$projectsWithOverdueTasks = Project::whereHas('tasks.assignedUsers', function ($query) use ($user) {
    $query->where('user_id', $user->id)
          ->where('task_assignments.status', '!=', 'completed');
})
->whereHas('tasks', function ($query) {
    $query->where('due_date', '<', now())
          ->whereNotIn('status', ['completed', 'cancelled']);
})
->with(['tasks' => function ($query) use ($user) {
    $query->whereHas('assignedUsers', function ($q) use ($user) {
        $q->where('user_id', $user->id);
    })->where('due_date', '<', now());
}])
->get();
```

### 2. Complex Task Assignment Queries

```php
// Get tasks with multiple assignees and their completion status
$tasksWithMultipleAssignees = Task::withCount([
    'assignedUsers',
    'assignedUsers as completed_assignees_count' => function ($query) {
        $query->where('task_assignments.status', 'completed');
    }
])
->having('assigned_users_count', '>', 1)
->with(['assignedUsers' => function ($query) {
    $query->withPivot(['role', 'status', 'completed_at', 'hours_logged']);
}])
->get();

// Get user's task workload analysis
$userWorkload = TaskAssignment::where('user_id', $user->id)
    ->whereIn('status', ['pending', 'in_progress'])
    ->with(['task.project'])
    ->get()
    ->groupBy('role')
    ->map(function ($assignments, $role) {
        return [
            'role' => $role,
            'count' => $assignments->count(),
            'overdue_count' => $assignments->filter(fn($a) => $a->isOverdue())->count(),
            'total_hours_logged' => $assignments->sum('hours_logged'),
            'projects' => $assignments->pluck('task.project.name')->unique()->values(),
        ];
    });
```

### 3. Nested Comment Queries

```php
// Get comment threads with nested replies (up to 3 levels deep)
$commentThreads = Comment::where('commentable_type', Project::class)
    ->where('commentable_id', $project->id)
    ->whereNull('parent_id')
    ->with([
        'replies.replies.replies',
        'user',
        'replies.user',
        'replies.replies.user',
        'replies.replies.replies.user'
    ])
    ->orderBy('created_at', 'desc')
    ->get();

// Get comments with high engagement (replies + reactions)
$popularComments = Comment::withCount(['replies', 'reactions'])
    ->having('replies_count', '>', 2)
    ->orHaving('reactions_count', '>', 5)
    ->with(['user', 'commentable'])
    ->orderBy('replies_count', 'desc')
    ->orderBy('reactions_count', 'desc')
    ->get();

// Get comment moderation queue with risk scoring
$moderationQueue = Comment::where('moderation_status', 'pending')
    ->orWhere(function ($query) {
        $query->where('spam_score', '>', 70)
              ->orWhere('toxicity_score', '>', 70)
              ->orWhere('report_count', '>', 2);
    })
    ->with(['user', 'commentable', 'reports.reporter'])
    ->orderByRaw('(spam_score + toxicity_score + (report_count * 10)) DESC')
    ->get();
```

### 4. Polymorphic Tag Queries

```php
// Get most popular tags across all models
$popularTags = Tag::withCount([
    'projects',
    'tasks', 
    'users',
    'organizations'
])
->orderByRaw('(projects_count + tasks_count + users_count + organizations_count) DESC')
->limit(20)
->get();

// Get related content by tags
$relatedContent = collect();

// Find projects with similar tags
$projectTags = $project->tags->pluck('id');
$relatedProjects = Project::whereHas('tags', function ($query) use ($projectTags) {
    $query->whereIn('tag_id', $projectTags);
})
->where('id', '!=', $project->id)
->withCount(['tags' => function ($query) use ($projectTags) {
    $query->whereIn('tag_id', $projectTags);
}])
->orderBy('tags_count', 'desc')
->limit(5)
->get();

$relatedContent = $relatedContent->merge($relatedProjects);

// Find tasks with similar tags
$relatedTasks = Task::whereHas('tags', function ($query) use ($projectTags) {
    $query->whereIn('tag_id', $projectTags);
})
->withCount(['tags' => function ($query) use ($projectTags) {
    $query->whereIn('tag_id', $projectTags);
}])
->orderBy('tags_count', 'desc')
->limit(5)
->get();

$relatedContent = $relatedContent->merge($relatedTasks);
```

### 5. File Attachment Queries with Versioning

```php
// Get latest versions of all attachments for a project
$latestAttachments = Attachment::where('attachable_type', Project::class)
    ->where('attachable_id', $project->id)
    ->where('is_current_version', true)
    ->with(['uploader', 'versions'])
    ->orderBy('created_at', 'desc')
    ->get();

// Get file usage statistics across organization
$fileStats = Attachment::where('tenant_id', $organization->id)
    ->selectRaw('
        COUNT(*) as total_files,
        SUM(size) as total_size,
        AVG(size) as average_size,
        COUNT(DISTINCT attachable_type) as model_types_count,
        COUNT(DISTINCT uploaded_by) as uploaders_count
    ')
    ->first();

// Get duplicate files by hash
$duplicateFiles = Attachment::select('hash', DB::raw('COUNT(*) as count'))
    ->where('tenant_id', $organization->id)
    ->groupBy('hash')
    ->having('count', '>', 1)
    ->with(['attachments' => function ($query) {
        $query->with(['uploader', 'attachable']);
    }])
    ->get();
```
## Performance Optimization Strategies

### 1. Eager Loading Patterns

```php
// Optimized project loading with all relationships
$projects = Project::with([
    'organization:id,name',
    'creator:id,name,email',
    'assignee:id,name,email',
    'tasks' => function ($query) {
        $query->select('id', 'project_id', 'title', 'status', 'due_date')
              ->where('status', '!=', 'completed');
    },
    'tasks.assignedUsers:id,name',
    'tags:id,name,color',
    'comments' => function ($query) {
        $query->latest()->limit(5);
    },
    'comments.user:id,name',
    'attachments' => function ($query) {
        $query->where('is_current_version', true)
              ->select('id', 'attachable_id', 'name', 'size', 'mime_type');
    }
])
->whereHas('organization.members', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})
->paginate(20);

// Prevent N+1 queries in nested relationships
$comments = Comment::with([
    'user:id,name,avatar',
    'replies.user:id,name,avatar',
    'replies.replies.user:id,name,avatar',
    'mentionedUsers:id,name',
    'reactions' => function ($query) {
        $query->select('comment_id', 'type', DB::raw('COUNT(*) as count'))
              ->groupBy('comment_id', 'type');
    }
])
->where('commentable_type', Project::class)
->where('commentable_id', $project->id)
->whereNull('parent_id')
->orderBy('created_at', 'desc')
->get();
```

### 2. Subquery Relationships for Aggregates

```php
// Add aggregate columns without separate queries
$projects = Project::addSelect([
    'tasks_count' => Task::selectRaw('COUNT(*)')
        ->whereColumn('project_id', 'projects.id'),
    'completed_tasks_count' => Task::selectRaw('COUNT(*)')
        ->whereColumn('project_id', 'projects.id')
        ->where('status', 'completed'),
    'overdue_tasks_count' => Task::selectRaw('COUNT(*)')
        ->whereColumn('project_id', 'projects.id')
        ->where('due_date', '<', now())
        ->whereNotIn('status', ['completed', 'cancelled']),
    'total_budget' => Task::selectRaw('SUM(estimated_hours * 50)') // $50/hour rate
        ->whereColumn('project_id', 'projects.id'),
    'latest_activity' => Comment::select('created_at')
        ->whereColumn('commentable_id', 'projects.id')
        ->where('commentable_type', Project::class)
        ->latest()
        ->limit(1)
])
->get();

// User dashboard with aggregated data
$userDashboard = User::addSelect([
    'assigned_tasks_count' => TaskAssignment::selectRaw('COUNT(*)')
        ->whereColumn('user_id', 'users.id')
        ->whereIn('status', ['pending', 'in_progress']),
    'completed_tasks_count' => TaskAssignment::selectRaw('COUNT(*)')
        ->whereColumn('user_id', 'users.id')
        ->where('status', 'completed'),
    'total_hours_logged' => TaskAssignment::selectRaw('SUM(hours_logged)')
        ->whereColumn('user_id', 'users.id'),
    'organizations_count' => OrganizationUser::selectRaw('COUNT(*)')
        ->whereColumn('user_id', 'users.id')
        ->where('is_active', true),
])
->find($user->id);
```

### 3. Efficient Polymorphic Queries

```php
// Optimized polymorphic queries with type constraints
$recentActivity = collect();

// Get recent projects
$recentProjects = Project::select('id', 'name', 'created_at', 'created_by')
    ->with('creator:id,name')
    ->where('created_at', '>', now()->subDays(7))
    ->latest()
    ->limit(10)
    ->get()
    ->map(function ($project) {
        return [
            'type' => 'project',
            'id' => $project->id,
            'title' => $project->name,
            'created_at' => $project->created_at,
            'creator' => $project->creator->name,
        ];
    });

// Get recent tasks
$recentTasks = Task::select('id', 'title', 'created_at', 'created_by')
    ->with('creator:id,name')
    ->where('created_at', '>', now()->subDays(7))
    ->latest()
    ->limit(10)
    ->get()
    ->map(function ($task) {
        return [
            'type' => 'task',
            'id' => $task->id,
            'title' => $task->title,
            'created_at' => $task->created_at,
            'creator' => $task->creator->name,
        ];
    });

// Merge and sort by date
$recentActivity = $recentProjects->merge($recentTasks)
    ->sortByDesc('created_at')
    ->take(20)
    ->values();
```

### 4. Caching Strategies

```php
// Cache expensive relationship queries
class ProjectService
{
    public function getProjectStatistics(Project $project): array
    {
        return Cache::remember(
            "project_stats_{$project->id}",
            now()->addMinutes(30),
            function () use ($project) {
                return [
                    'tasks_count' => $project->tasks()->count(),
                    'completed_tasks' => $project->tasks()->where('status', 'completed')->count(),
                    'team_members' => $project->involvedUsers()->count(),
                    'comments_count' => $project->comments()->count(),
                    'attachments_size' => $project->attachments()->sum('size'),
                    'budget_utilization' => $project->getBudgetAnalysis(),
                ];
            }
        );
    }

    public function getUserWorkload(User $user): array
    {
        return Cache::remember(
            "user_workload_{$user->id}",
            now()->addMinutes(15),
            function () use ($user) {
                return [
                    'active_tasks' => $user->assignedTasks()
                        ->whereNotIn('status', ['completed', 'cancelled'])
                        ->count(),
                    'overdue_tasks' => $user->overdueTasks()->count(),
                    'projects_involved' => $user->allProjects()->count(),
                    'hours_this_week' => $user->taskAssignments()
                        ->whereHas('timeEntries', function ($query) {
                            $query->whereBetween('logged_at', [
                                now()->startOfWeek(),
                                now()->endOfWeek()
                            ]);
                        })
                        ->sum('hours'),
                ];
            }
        );
    }
}

// Cache invalidation on model changes
class Project extends Model
{
    protected static function booted(): void
    {
        static::saved(function (Project $project) {
            Cache::forget("project_stats_{$project->id}");
            
            // Clear related user caches
            $project->involvedUsers()->each(function ($user) {
                Cache::forget("user_workload_{$user->id}");
            });
        });
    }
}
```

## Testing Relationship Integrity

### 1. Factory Relationships

```php
// database/factories/ProjectFactory.php
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Organization::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['maintenance', 'improvement', 'inspection']),
            'status' => $this->faker->randomElement(['planning', 'active', 'completed']),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            'created_by' => User::factory(),
            'start_date' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'due_date' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'budget' => $this->faker->randomFloat(2, 1000, 50000),
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state([
            'tenant_id' => $organization->id,
            'projectable_type' => Organization::class,
            'projectable_id' => $organization->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'projectable_type' => User::class,
            'projectable_id' => $user->id,
            'created_by' => $user->id,
        ]);
    }

    public function withTasks(int $count = 3): static
    {
        return $this->afterCreating(function (Project $project) use ($count) {
            Task::factory($count)->create([
                'project_id' => $project->id,
                'tenant_id' => $project->tenant_id,
            ]);
        });
    }

    public function withTeam(int $memberCount = 3): static
    {
        return $this->afterCreating(function (Project $project) use ($memberCount) {
            $users = User::factory($memberCount)->create([
                'tenant_id' => $project->tenant_id,
            ]);

            $tasks = $project->tasks;
            
            foreach ($tasks as $task) {
                $assignees = $users->random(rand(1, 2));
                foreach ($assignees as $user) {
                    $task->assignUser($user, 'assignee');
                }
                
                // Add a reviewer
                $reviewer = $users->except($assignees->pluck('id'))->first();
                if ($reviewer) {
                    $task->assignUser($reviewer, 'reviewer');
                }
            }
        });
    }
}

// database/factories/TaskAssignmentFactory.php
class TaskAssignmentFactory extends Factory
{
    protected $model = TaskAssignment::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'role' => $this->faker->randomElement(['assignee', 'reviewer', 'observer']),
            'assigned_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'hours_logged' => $this->faker->randomFloat(2, 0, 40),
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function asAssignee(): static
    {
        return $this->state(['role' => 'assignee']);
    }

    public function asReviewer(): static
    {
        return $this->state(['role' => 'reviewer']);
    }
}
```

### 2. Relationship Tests

```php
// tests/Unit/Models/ProjectRelationshipsTest.php
class ProjectRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_belongs_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();

        $this->assertInstanceOf(Organization::class, $project->organization);
        $this->assertEquals($organization->id, $project->organization->id);
    }

    public function test_project_has_many_tasks(): void
    {
        $project = Project::factory()->withTasks(5)->create();

        $this->assertCount(5, $project->tasks);
        $this->assertInstanceOf(Task::class, $project->tasks->first());
    }

    public function test_project_polymorphic_ownership(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $userProject = Project::factory()->forUser($user)->create();
        $orgProject = Project::factory()->forOrganization($organization)->create();

        $this->assertInstanceOf(User::class, $userProject->projectable);
        $this->assertInstanceOf(Organization::class, $orgProject->projectable);
    }

    public function test_project_involved_users_through_tasks(): void
    {
        $project = Project::factory()->withTasks(3)->withTeam(5)->create();

        $involvedUsers = $project->involvedUsers;
        
        $this->assertGreaterThan(0, $involvedUsers->count());
        $this->assertInstanceOf(User::class, $involvedUsers->first());
    }

    public function test_cascade_delete_maintains_integrity(): void
    {
        $project = Project::factory()->withTasks(3)->create();
        $taskIds = $project->tasks->pluck('id');

        $project->delete();

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        
        foreach ($taskIds as $taskId) {
            $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
        }
    }
}

// tests/Unit/Models/TaskAssignmentTest.php
class TaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_assignment_pivot_relationships(): void
    {
        $task = Task::factory()->create();
        $user = User::factory()->create();
        
        $assignment = TaskAssignment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'role' => 'assignee',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Task::class, $assignment->task);
        $this->assertInstanceOf(User::class, $assignment->user);
        $this->assertEquals($task->id, $assignment->task->id);
        $this->assertEquals($user->id, $assignment->user->id);
    }

    public function test_task_completion_workflow(): void
    {
        $task = Task::factory()->create();
        $assignee = User::factory()->create();
        $reviewer = User::factory()->create();

        $task->assignUser($assignee, 'assignee');
        $task->assignUser($reviewer, 'reviewer');

        // Complete as assignee
        $task->markCompletedBy($assignee, 'Work completed');

        $assignment = $task->assignedUsers()
            ->where('user_id', $assignee->id)
            ->first();

        $this->assertEquals('completed', $assignment->pivot->status);
        $this->assertNotNull($assignment->pivot->completed_at);
        $this->assertEquals('Work completed', $assignment->pivot->notes);
    }

    public function test_role_based_permissions(): void
    {
        $assignment = TaskAssignment::factory()->asAssignee()->create();

        $this->assertTrue($assignment->canPerformAction('complete'));
        $this->assertTrue($assignment->canPerformAction('log_time'));
        $this->assertFalse($assignment->canPerformAction('review'));

        $reviewerAssignment = TaskAssignment::factory()->asReviewer()->create();
        
        $this->assertFalse($reviewerAssignment->canPerformAction('complete'));
        $this->assertTrue($reviewerAssignment->canPerformAction('log_time'));
    }
}
```

This comprehensive implementation provides:

1. **Complete database migrations** with proper indexing and constraints
2. **Enhanced model relationships** with polymorphic support
3. **Pivot models** with business logic and validation
4. **Reusable traits** for common relationship patterns
5. **Complex querying examples** for real-world scenarios
6. **Performance optimization** strategies and caching
7. **Comprehensive testing** approach for relationship integrity

The system supports multi-tenancy, role-based permissions, audit trails, and complex business workflows while maintaining clean, maintainable code structure.