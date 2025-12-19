<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\TaskAssignment;
use App\Models\TimeEntry;
use App\Services\RelationshipQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Relationship Query Service Tests
 * 
 * Tests complex relationship queries and analytics
 */
class RelationshipQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private RelationshipQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RelationshipQueryService();
    }

    public function test_get_user_workload_returns_comprehensive_data(): void
    {
        // Create test data
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        
        // Add user to organization
        $organization->addMember($user, 'member');
        
        // Create projects and tasks
        $project1 = Project::factory()->forOrganization($organization)->create(['status' => 'active']);
        $project2 = Project::factory()->forOrganization($organization)->create(['status' => 'completed']);
        
        $task1 = Task::factory()->create(['project_id' => $project1->id, 'status' => 'pending']);
        $task2 = Task::factory()->create(['project_id' => $project1->id, 'status' => 'completed']);
        $task3 = Task::factory()->create([
            'project_id' => $project1->id, 
            'status' => 'pending',
            'due_date' => now()->subDays(1) // overdue
        ]);
        
        // Assign tasks to user
        $task1->assignUser($user, 'assignee');
        $task2->assignUser($user, 'assignee');
        $task3->assignUser($user, 'reviewer');
        
        // Add time entries
        TimeEntry::factory()->create([
            'user_id' => $user->id,
            'task_id' => $task1->id,
            'hours' => 8,
            'logged_at' => now()->startOfWeek()->addDay()
        ]);

        $workload = $this->service->getUserWorkload($user);

        $this->assertIsArray($workload);
        $this->assertArrayHasKey('projects_count', $workload);
        $this->assertArrayHasKey('active_projects_count', $workload);
        $this->assertArrayHasKey('total_tasks_count', $workload);
        $this->assertArrayHasKey('assigned_tasks_count', $workload);
        $this->assertArrayHasKey('weekly_hours', $workload);
        
        $this->assertEquals(2, $workload['projects_count']);
        $this->assertEquals(1, $workload['active_projects_count']);
        $this->assertEquals(2, $workload['assigned_tasks_count']); // pending tasks only
        $this->assertEquals(8, $workload['weekly_hours']);
    }

    public function test_get_project_collaboration_network(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        
        // Create users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        
        // Create tasks
        $task1 = Task::factory()->create(['project_id' => $project->id]);
        $task2 = Task::factory()->create(['project_id' => $project->id]);
        
        // Create shared assignments
        $task1->assignUser($user1, 'assignee');
        $task1->assignUser($user2, 'reviewer');
        
        $task2->assignUser($user1, 'assignee');
        $task2->assignUser($user3, 'reviewer');
        
        // Log some hours
        TaskAssignment::where('user_id', $user1->id)->first()->update(['hours_logged' => 10]);
        TaskAssignment::where('user_id', $user2->id)->first()->update(['hours_logged' => 5]);

        $network = $this->service->getProjectCollaborationNetwork($project);

        $this->assertIsArray($network);
        $this->assertArrayHasKey('involved_users', $network);
        $this->assertArrayHasKey('collaborations', $network);
        
        $this->assertCount(3, $network['involved_users']);
        $this->assertGreaterThan(0, count($network['collaborations']));
        
        // Check user data structure
        $user1Data = collect($network['involved_users'])->firstWhere('id', $user1->id);
        $this->assertNotNull($user1Data);
        $this->assertEquals(10, $user1Data['total_hours']);
        $this->assertEquals(2, $user1Data['tasks_count']);
    }

    public function test_get_comment_engagement_analytics(): void
    {
        $project = Project::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Create comments with reactions
        $comment1 = Comment::factory()->create([
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
            'user_id' => $user1->id,
            'body' => 'Great project!'
        ]);
        
        $comment2 = Comment::factory()->create([
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
            'user_id' => $user2->id,
            'body' => 'I agree!',
            'parent_id' => $comment1->id,
            'depth' => 1
        ]);
        
        // Add reactions
        $comment1->addReaction($user2, 'like');
        $comment1->addReaction($user1, 'heart');
        $comment2->addReaction($user1, 'like');

        $analytics = $this->service->getCommentEngagementAnalytics($project);

        $this->assertIsArray($analytics);
        $this->assertEquals(2, $analytics['total_comments']);
        $this->assertEquals(1, $analytics['total_replies']);
        $this->assertEquals(3, $analytics['total_reactions']);
        $this->assertEquals(1.5, $analytics['avg_reactions_per_comment']);
        
        $this->assertArrayHasKey('active_commenters', $analytics);
        $this->assertArrayHasKey('reaction_distribution', $analytics);
        $this->assertArrayHasKey('thread_depths', $analytics);
    }

    public function test_get_tag_relationship_analysis(): void
    {
        $organization = Organization::factory()->create();
        
        // Create tags
        $tag1 = Tag::factory()->create(['name' => 'urgent', 'tenant_id' => $organization->id]);
        $tag2 = Tag::factory()->create(['name' => 'bug', 'tenant_id' => $organization->id]);
        $tag3 = Tag::factory()->create(['name' => 'feature', 'tenant_id' => $organization->id]);
        
        // Create projects and tasks with tags
        $project1 = Project::factory()->forOrganization($organization)->create();
        $project2 = Project::factory()->forOrganization($organization)->create();
        
        $task1 = Task::factory()->create(['project_id' => $project1->id]);
        $task2 = Task::factory()->create(['project_id' => $project2->id]);
        
        // Tag items (urgent + bug appear together)
        $project1->tag(['urgent', 'bug']);
        $task1->tag(['urgent', 'bug']);
        $task2->tag(['urgent', 'feature']);

        $analysis = $this->service->getTagRelationshipAnalysis($tag1);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('tag', $analysis);
        $this->assertArrayHasKey('co_occurring_tags', $analysis);
        $this->assertArrayHasKey('usage_by_model', $analysis);
        
        $this->assertEquals($tag1->id, $analysis['tag']->id);
        $this->assertGreaterThan(0, count($analysis['co_occurring_tags']));
        
        // Check that 'bug' appears as co-occurring (appears with 'urgent' twice)
        $bugTag = collect($analysis['co_occurring_tags'])->firstWhere('name', 'bug');
        $this->assertNotNull($bugTag);
        $this->assertEquals(2, $bugTag->co_occurrence_count);
    }

    public function test_cross_model_search(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->addMember($user, 'member');
        
        // Create searchable content
        $project = Project::factory()->forOrganization($organization)->create([
            'name' => 'Test Project with Laravel',
            'description' => 'A project using Laravel framework'
        ]);
        
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'title' => 'Laravel migration task',
            'description' => 'Create Laravel database migrations'
        ]);
        
        $comment = Comment::factory()->create([
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
            'user_id' => $user->id,
            'body' => 'This Laravel implementation looks good!'
        ]);
        
        $tag = Tag::factory()->create([
            'name' => 'laravel',
            'description' => 'Laravel framework related',
            'tenant_id' => $organization->id
        ]);

        $results = $this->service->crossModelSearch('Laravel', $user, 20);

        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));
        
        // Check that we get results from different model types
        $resultTypes = collect($results)->pluck('type')->unique();
        $this->assertContains('project', $resultTypes);
        $this->assertContains('task', $resultTypes);
        $this->assertContains('comment', $resultTypes);
        $this->assertContains('tag', $resultTypes);
        
        // Check result structure
        $projectResult = collect($results)->firstWhere('type', 'project');
        $this->assertNotNull($projectResult);
        $this->assertArrayHasKey('title', $projectResult);
        $this->assertArrayHasKey('description', $projectResult);
        $this->assertArrayHasKey('url', $projectResult);
        $this->assertArrayHasKey('context', $projectResult);
    }

    public function test_workload_caching(): void
    {
        $user = User::factory()->create();
        
        // First call should hit the database
        $workload1 = $this->service->getUserWorkload($user);
        
        // Second call should use cache
        $workload2 = $this->service->getUserWorkload($user);
        
        $this->assertEquals($workload1, $workload2);
    }

    public function test_collaboration_network_with_no_shared_tasks(): void
    {
        $organization = Organization::factory()->create();
        $project = Project::factory()->forOrganization($organization)->create();
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Create separate tasks (no collaboration)
        $task1 = Task::factory()->create(['project_id' => $project->id]);
        $task2 = Task::factory()->create(['project_id' => $project->id]);
        
        $task1->assignUser($user1, 'assignee');
        $task2->assignUser($user2, 'assignee');

        $network = $this->service->getProjectCollaborationNetwork($project);

        $this->assertCount(2, $network['involved_users']);
        $this->assertEmpty($network['collaborations']); // No shared tasks = no collaborations
    }

    public function test_comment_analytics_with_no_comments(): void
    {
        $project = Project::factory()->create();

        $analytics = $this->service->getCommentEngagementAnalytics($project);

        $this->assertEquals(0, $analytics['total_comments']);
        $this->assertEquals(0, $analytics['total_replies']);
        $this->assertEquals(0, $analytics['total_reactions']);
        $this->assertEquals(0, $analytics['avg_reactions_per_comment']);
        $this->assertEquals(0, $analytics['engagement_rate']);
    }
}