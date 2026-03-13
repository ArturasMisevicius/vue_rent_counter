# Advanced Eloquent Relationships - Usage Examples

## Complex Query Examples

### 1. Get All Projects for a User Across Organizations

```php
// Get all projects where user is involved (creator, assignee, or task assignee)
$userProjects = Project::where(function ($query) use ($user) {
    $query->where('created_by', $user->id)
          ->orWhere('assigned_to', $user->id);
})->orWhereHas('tasks.assignedUsers', function ($query) use ($user) {
    $query->where('user_id', $user->id);
})->with([
    'projectable',
    'tasks' => function ($query) use ($user) {
        $query->whereHas('assignedUsers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    },
    'tasks.assignedUsers'
])->get();

// Alternative: Using the User model method
$userProjects = $user->allProjects()->with(['projectable', 'tasks'])->get();
```

### 2. Get Tasks with Assignees and Their Roles

```php
$tasksWithAssignees = Task::with([
    'assignedUsers' => function ($query) {
        $query->withPivot(['role', 'assigned_at', 'completed_at', 'notes']);
    },
    'project.projectable',
    'creator:id,name,email'
])->where('status', 'active')
  ->orderBy('priority')
  ->orderBy('due_date')
  ->get();

// Access pivot data
foreach ($tasksWithAssignees as $task) {
    foreach ($task->assignedUsers as $user) {
        echo "User: {$user->name}, Role: {$user->pivot->role}";
        if ($user->pivot->completed_at) {
            echo " (Completed: {$user->pivot->completed_at})";
        }
    }
}
```

### 3. Nested Comments Thread

```php
// Get all comments in a thread with nested replies
$commentsThread = Comment::where('commentable_type', Property::class)
    ->where('commentable_id', $propertyId)
    ->with([
        'user:id,name,email',
        'replies' => function ($query) {
            $query->with([
                'user:id,name,email',
                'replies.user:id,name,email'
            ])->orderBy('created_at');
        }
    ])
    ->whereNull('parent_id')
    ->orderBy('is_pinned', 'desc')
    ->orderBy('created_at')
    ->get();

// Alternative: Using path-based ordering for better performance
$commentsFlat = Comment::where('commentable_type', Property::class)
    ->where('commentable_id', $propertyId)
    ->with('user:id,name,email')
    ->orderBy('path')
    ->orderBy('sort_order')
    ->get();
```

### 4. Complex Multi-Model Tag Queries

```php
// Get all items tagged with 'urgent' across different models
$urgentItems = collect();

// Properties
$urgentProperties = Property::withTag('urgent')
    ->with(['building:id,name', 'tenants:id,name'])
    ->get()
    ->map(function ($item) {
        $item->model_type = 'Property';
        return $item;
    });

// Projects
$urgentProjects = Project::withTag('urgent')
    ->with(['projectable', 'creator:id,name'])
    ->get()
    ->map(function ($item) {
        $item->model_type = 'Project';
        return $item;
    });

// Tasks
$urgentTasks = Task::withTag('urgent')
    ->with(['project:id,name', 'assignedUsers:id,name'])
    ->get()
    ->map(function ($item) {
        $item->model_type = 'Task';
        return $item;
    });

$urgentItems = $urgentProperties->concat($urgentProjects)->concat($urgentTasks);
```

### 5. Advanced Relationship Existence Queries

```php
// Properties with active projects but no overdue tasks
$properties = Property::whereHas('projects', function ($query) {
    $query->where('status', 'active');
})->whereDoesntHave('projects.tasks', function ($query) {
    $query->where('due_date', '<', now())
          ->whereNotIn('status', ['completed', 'cancelled']);
})->with(['building:id,name', 'activeProjects'])->get();

// Users with admin role in any organization
$admins = User::whereHas('organizations', function ($query) {
    $query->wherePivot('role', 'admin')
          ->wherePivot('is_active', true);
})->with(['organizations' => function ($query) {
    $query->withPivot(['role', 'joined_at']);
}])->get();

// Projects with all tasks completed
$completedProjects = Project::whereDoesntHave('tasks', function ($query) {
    $query->whereNotIn('status', ['completed', 'cancelled']);
})->where('status', 'active')
  ->with(['projectable', 'tasks'])
  ->get();
```

## Practical Usage Scenarios

### 1. Creating a Project with Tasks and Assignments

```php
DB::transaction(function () use ($propertyId, $userId) {
    // Create project
    $project = Project::create([
        'tenant_id' => auth()->user()->tenant_id,
        'name' => 'Bathroom Renovation',
        'description' => 'Complete renovation of apartment bathroom',
        'type' => 'improvement',
        'scope' => 'property',
        'projectable_type' => Property::class,
        'projectable_id' => $propertyId,
        'created_by' => auth()->id(),
        'assigned_to' => $userId,
        'status' => 'active',
        'priority' => 'high',
        'start_date' => now(),
        'due_date' => now()->addWeeks(4),
        'budget' => 5000.00,
    ]);

    // Add tags
    $project->attachTags(['renovation', 'bathroom', 'high-priority']);

    // Create tasks
    $tasks = [
        [
            'title' => 'Remove old fixtures',
            'description' => 'Remove toilet, sink, and bathtub',
            'priority' => 'high',
            'estimated_hours' => 8,
            'due_date' => now()->addDays(3),
        ],
        [
            'title' => 'Install new plumbing',
            'description' => 'Update pipes and install new fixtures',
            'priority' => 'high',
            'estimated_hours' => 16,
            'due_date' => now()->addWeeks(2),
        ],
        [
            'title' => 'Tile installation',
            'description' => 'Install wall and floor tiles',
            'priority' => 'medium',
            'estimated_hours' => 12,
            'due_date' => now()->addWeeks(3),
        ],
    ];

    foreach ($tasks as $taskData) {
        $task = $project->tasks()->create(array_merge($taskData, [
            'tenant_id' => $project->tenant_id,
            'created_by' => auth()->id(),
        ]));

        // Assign users to tasks
        $task->assignUser(User::find($userId), 'assignee');
        
        // Add supervisor as reviewer
        if ($supervisorId = User::where('role', 'manager')->first()?->id) {
            $task->assignUser(User::find($supervisorId), 'reviewer');
        }
    }

    // Add initial comment
    $project->addComment(
        'Project created and initial tasks assigned. Please review timeline.',
        auth()->user(),
        true // internal comment
    );
});
```

### 2. Task Completion Workflow

```php
// Mark task as completed by assignee
$task = Task::with(['assignedUsers', 'project'])->find($taskId);
$user = auth()->user();

if ($task->assignedUsers->contains($user)) {
    // Mark user's assignment as completed
    $task->markCompletedBy($user);
    
    // Add completion comment
    $task->addComment(
        "Task completed by {$user->name}. Actual time: {$actualHours} hours.",
        $user
    );
    
    // Update actual hours
    $task->update(['actual_hours' => $actualHours]);
    
    // Notify reviewers if task is completed
    if ($task->isCompleted()) {
        $reviewers = $task->reviewers;
        foreach ($reviewers as $reviewer) {
            // Send notification (implement your notification logic)
            $reviewer->notify(new TaskCompletedNotification($task));
        }
    }
}
```

### 3. Organization Member Management

```php
// Add user to organization with specific role and permissions
$organization = Organization::find($orgId);
$user = User::find($userId);

$organization->members()->attach($user->id, [
    'role' => 'manager',
    'permissions' => ['manage_properties', 'view_reports', 'manage_tenants'],
    'joined_at' => now(),
    'invited_by' => auth()->id(),
]);

// Update user permissions
$membership = OrganizationUser::where('organization_id', $orgId)
    ->where('user_id', $userId)
    ->first();

$membership->addPermission('manage_invoices');
$membership->removePermission('manage_tenants');

// Check permissions
if ($user->hasRoleInOrganization($organization, 'admin')) {
    // User has admin role
}

$userRole = $user->getRoleInOrganization($organization);
```

### 4. Advanced Comment System Usage

```php
// Add a comment with mentions
$property = Property::find($propertyId);
$comment = $property->addComment(
    'There seems to be a leak in the bathroom. @john.doe please investigate.',
    auth()->user()
);

// Reply to comment
$reply = $property->replyToComment(
    $comment,
    'I will check it tomorrow morning.',
    User::where('email', 'john.doe@example.com')->first()
);

// Pin important comment
$comment->update(['is_pinned' => true]);

// Resolve comment thread
$comment->update([
    'is_resolved' => true,
    'resolved_by' => auth()->id(),
    'resolved_at' => now(),
]);

// Get comment statistics
$stats = [
    'total_comments' => $property->comment_count,
    'unresolved_comments' => $property->unresolved_comment_count,
    'public_comments' => $property->public_comment_count,
    'internal_comments' => $property->internal_comment_count,
];
```

### 5. File Attachment Management

```php
// Attach files to a project
$project = Project::find($projectId);

// Single file upload
if ($request->hasFile('document')) {
    $attachment = $project->attachFile(
        $request->file('document'),
        'Project specification document',
        'document',
        auth()->user(),
        false // not public
    );
}

// Multiple file upload
if ($request->hasFile('photos')) {
    $attachments = $project->attachFiles(
        $request->file('photos'),
        'progress_photos',
        auth()->user(),
        true // public
    );
}

// Get attachments by category
$documents = $project->attachmentsByCategory('document');
$photos = $project->attachmentsByCategory('progress_photos');

// Check attachment statistics
$stats = [
    'total_attachments' => $project->attachment_count,
    'total_size' => $project->human_attachment_size,
    'has_images' => $project->hasImages(),
    'has_documents' => $project->hasDocuments(),
];
```

## Performance Optimization Examples

### 1. Efficient Eager Loading

```php
// Load projects with optimized relationships
$projects = Project::with([
    'projectable:id,address,type', // Only needed columns
    'creator:id,name,email',
    'assignee:id,name,email',
    'tasks' => function ($query) {
        $query->select(['id', 'project_id', 'title', 'status', 'priority'])
              ->where('status', '!=', 'cancelled')
              ->with('assignedUsers:id,name,email');
    },
    'tags:id,name,color',
    'topLevelComments' => function ($query) {
        $query->with('user:id,name')
              ->latest()
              ->limit(3); // Only recent comments
    }
])->withCount([
    'tasks',
    'tasks as completed_tasks_count' => function ($query) {
        $query->where('status', 'completed');
    },
    'comments as unresolved_comments_count' => function ($query) {
        $query->where('is_resolved', false);
    }
])->get();
```

### 2. Subquery Relationships

```php
// Use subqueries for better performance
$properties = Property::select(['id', 'address', 'tenant_id'])
    ->addSelect([
        'latest_project_name' => Project::select('name')
            ->whereColumn('projectable_id', 'properties.id')
            ->where('projectable_type', Property::class)
            ->latest()
            ->limit(1),
        'active_projects_count' => Project::selectRaw('count(*)')
            ->whereColumn('projectable_id', 'properties.id')
            ->where('projectable_type', Property::class)
            ->where('status', 'active'),
    ])
    ->get();
```

### 3. Avoiding N+1 Queries

```php
// Bad: N+1 query problem
$projects = Project::all();
foreach ($projects as $project) {
    echo $project->creator->name; // N+1 query
    echo $project->tasks->count(); // N+1 query
}

// Good: Eager loading
$projects = Project::with(['creator:id,name', 'tasks:id,project_id'])
    ->withCount('tasks')
    ->get();

foreach ($projects as $project) {
    echo $project->creator->name; // No additional query
    echo $project->tasks_count; // No additional query
}
```

This implementation provides a comprehensive foundation for advanced Eloquent relationships in your property management system, with real-world examples and performance considerations.