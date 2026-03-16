<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\Comment;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Relationship Query Service
 * 
 * Provides optimized queries for complex relationship scenarios
 */
class RelationshipQueryService
{
    /**
     * Get user's complete workload across all organizations
     */
    public function getUserWorkload(User $user): array
    {
        return Cache::remember(
            "user_workload_{$user->id}",
            now()->addMinutes(15),
            function () use ($user) {
                // Get all projects user is involved in
                $projects = Project::whereHas('organization.members', function ($query) use ($user) {
                    $query->where('user_id', $user->id)->where('is_active', true);
                })
                ->orWhere('created_by', $user->id)
                ->orWhere('assigned_to', $user->id)
                ->orWhere(function ($query) use ($user) {
                    $query->where('projectable_type', User::class)
                          ->where('projectable_id', $user->id);
                })
                ->withCount([
                    'tasks',
                    'tasks as active_tasks_count' => function ($query) {
                        $query->whereNotIn('status', ['completed', 'cancelled']);
                    },
                    'tasks as overdue_tasks_count' => function ($query) {
                        $query->where('due_date', '<', now())
                              ->whereNotIn('status', ['completed', 'cancelled']);
                    }
                ])
                ->get();

                // Get direct task assignments
                $taskAssignments = $user->taskAssignments()
                    ->with(['task.project'])
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->get();

                // Calculate time logged this week
                $weeklyHours = $user->taskAssignments()
                    ->join('time_entries', 'task_assignments.id', '=', 'time_entries.assignment_id')
                    ->whereBetween('time_entries.logged_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])
                    ->sum('time_entries.hours');

                return [
                    'projects_count' => $projects->count(),
                    'active_projects_count' => $projects->where('status', 'active')->count(),
                    'total_tasks_count' => $projects->sum('tasks_count'),
                    'active_tasks_count' => $projects->sum('active_tasks_count'),
                    'overdue_tasks_count' => $projects->sum('overdue_tasks_count'),
                    'assigned_tasks_count' => $taskAssignments->count(),
                    'weekly_hours' => $weeklyHours,
                    'task_assignments_by_role' => $taskAssignments->groupBy('role')->map->count(),
                    'projects_by_status' => $projects->groupBy('status')->map->count(),
                ];
            }
        );
    }

    /**
     * Get project collaboration network
     */
    public function getProjectCollaborationNetwork(Project $project): array
    {
        // Get all users involved in the project
        $involvedUsers = $project->involvedUsers()
            ->with(['taskAssignments' => function ($query) use ($project) {
                $query->whereHas('task', function ($q) use ($project) {
                    $q->where('project_id', $project->id);
                })->withPivot(['role', 'hours_logged']);
            }])
            ->get();

        // Build collaboration matrix
        $collaborations = [];
        
        foreach ($involvedUsers as $user1) {
            foreach ($involvedUsers as $user2) {
                if ($user1->id !== $user2->id) {
                    // Find shared tasks
                    $sharedTasks = Task::where('project_id', $project->id)
                        ->whereHas('assignedUsers', function ($query) use ($user1) {
                            $query->where('user_id', $user1->id);
                        })
                        ->whereHas('assignedUsers', function ($query) use ($user2) {
                            $query->where('user_id', $user2->id);
                        })
                        ->count();

                    if ($sharedTasks > 0) {
                        $collaborations[] = [
                            'user1_id' => $user1->id,
                            'user1_name' => $user1->name,
                            'user2_id' => $user2->id,
                            'user2_name' => $user2->name,
                            'shared_tasks' => $sharedTasks,
                            'collaboration_strength' => $sharedTasks / $project->tasks()->count(),
                        ];
                    }
                }
            }
        }

        return [
            'involved_users' => $involvedUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'roles' => $user->taskAssignments->pluck('role')->unique()->values(),
                    'total_hours' => $user->taskAssignments->sum('hours_logged'),
                    'tasks_count' => $user->taskAssignments->count(),
                ];
            }),
            'collaborations' => collect($collaborations)->sortByDesc('collaboration_strength')->values(),
        ];
    }

    /**
     * Get comment engagement analytics
     */
    public function getCommentEngagementAnalytics($commentable): array
    {
        $comments = Comment::where('commentable_type', get_class($commentable))
            ->where('commentable_id', $commentable->id)
            ->withCount(['replies', 'reactions'])
            ->with(['user', 'reactions'])
            ->get();

        // Calculate engagement metrics
        $totalComments = $comments->count();
        $totalReplies = $comments->sum('replies_count');
        $totalReactions = $comments->sum('reactions_count');
        
        // Get most active commenters
        $activeCommenters = $comments->groupBy('user_id')
            ->map(function ($userComments, $userId) {
                $user = $userComments->first()->user;
                return [
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'comments_count' => $userComments->count(),
                    'total_reactions_received' => $userComments->sum('reactions_count'),
                    'avg_reactions_per_comment' => $userComments->avg('reactions_count'),
                ];
            })
            ->sortByDesc('comments_count')
            ->take(10)
            ->values();

        // Get reaction distribution
        $reactionDistribution = $comments->flatMap->reactions
            ->groupBy('type')
            ->map->count()
            ->sortDesc();

        // Get comment thread depths
        $threadDepths = $comments->groupBy('depth')
            ->map->count()
            ->sortKeys();

        return [
            'total_comments' => $totalComments,
            'total_replies' => $totalReplies,
            'total_reactions' => $totalReactions,
            'avg_reactions_per_comment' => $totalComments > 0 ? $totalReactions / $totalComments : 0,
            'engagement_rate' => $totalComments > 0 ? ($totalReplies + $totalReactions) / $totalComments : 0,
            'active_commenters' => $activeCommenters,
            'reaction_distribution' => $reactionDistribution,
            'thread_depths' => $threadDepths,
            'most_engaged_comment' => $comments->sortByDesc('reactions_count')->first(),
        ];
    }

    /**
     * Get tag relationship analysis
     */
    public function getTagRelationshipAnalysis(Tag $tag, int $limit = 20): array
    {
        // Get co-occurring tags across all models
        $coOccurringTags = DB::table('taggables as t1')
            ->join('taggables as t2', function ($join) {
                $join->on('t1.taggable_type', '=', 't2.taggable_type')
                     ->on('t1.taggable_id', '=', 't2.taggable_id');
            })
            ->join('tags', 't2.tag_id', '=', 'tags.id')
            ->where('t1.tag_id', $tag->id)
            ->where('t2.tag_id', '!=', $tag->id)
            ->select('tags.*', DB::raw('COUNT(*) as co_occurrence_count'))
            ->groupBy('tags.id')
            ->orderBy('co_occurrence_count', 'desc')
            ->limit($limit)
            ->get();

        // Get usage across different model types
        $usageByModel = DB::table('taggables')
            ->where('tag_id', $tag->id)
            ->select('taggable_type', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('taggable_type')
            ->get()
            ->mapWithKeys(function ($item) {
                $modelName = class_basename($item->taggable_type);
                return [$modelName => $item->usage_count];
            });

        // Get recent usage trend (last 30 days)
        $usageTrend = DB::table('taggables')
            ->where('tag_id', $tag->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'tag' => $tag,
            'co_occurring_tags' => $coOccurringTags,
            'usage_by_model' => $usageByModel,
            'usage_trend' => $usageTrend,
            'total_usage' => $tag->usage_count,
            'relationship_strength' => $coOccurringTags->sum('co_occurrence_count'),
        ];
    }

    /**
     * Get cross-model search results
     */
    public function crossModelSearch(string $query, User $user, int $limit = 50): array
    {
        $results = collect();

        // Search projects
        $projects = Project::whereHas('organization.members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%");
        })
        ->with(['organization:id,name', 'creator:id,name'])
        ->limit($limit / 4)
        ->get()
        ->map(function ($project) {
            return [
                'type' => 'project',
                'id' => $project->id,
                'title' => $project->name,
                'description' => $project->description,
                'url' => route('projects.show', $project),
                'context' => $project->organization->name,
                'created_at' => $project->created_at,
            ];
        });

        // Search tasks
        $tasks = Task::whereHas('project.organization.members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
              ->orWhere('description', 'like', "%{$query}%");
        })
        ->with(['project:id,name', 'creator:id,name'])
        ->limit($limit / 4)
        ->get()
        ->map(function ($task) {
            return [
                'type' => 'task',
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'url' => route('tasks.show', $task),
                'context' => $task->project->name,
                'created_at' => $task->created_at,
            ];
        });

        // Search comments
        $comments = Comment::whereHasMorph('commentable', [Project::class, Task::class], function ($q, $type) use ($user) {
            if ($type === Project::class) {
                $q->whereHas('organization.members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            } elseif ($type === Task::class) {
                $q->whereHas('project.organization.members', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            }
        })
        ->where('body', 'like', "%{$query}%")
        ->with(['user:id,name', 'commentable'])
        ->limit($limit / 4)
        ->get()
        ->map(function ($comment) {
            return [
                'type' => 'comment',
                'id' => $comment->id,
                'title' => 'Comment by ' . $comment->user->name,
                'description' => Str::limit($comment->body, 100),
                'url' => $comment->commentable ? route(Str::plural(Str::lower(class_basename($comment->commentable_type))) . '.show', $comment->commentable) . '#comment-' . $comment->id : '#',
                'context' => $comment->commentable ? $comment->commentable->name ?? $comment->commentable->title : 'Unknown',
                'created_at' => $comment->created_at,
            ];
        });

        // Search tags
        $tags = Tag::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->withCount(['projects', 'tasks'])
            ->limit($limit / 4)
            ->get()
            ->map(function ($tag) {
                return [
                    'type' => 'tag',
                    'id' => $tag->id,
                    'title' => $tag->name,
                    'description' => $tag->description,
                    'url' => route('tags.show', $tag),
                    'context' => "Used in {$tag->projects_count} projects, {$tag->tasks_count} tasks",
                    'created_at' => $tag->created_at,
                ];
            });

        return $results->merge($projects)
            ->merge($tasks)
            ->merge($comments)
            ->merge($tags)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->toArray();
    }
}