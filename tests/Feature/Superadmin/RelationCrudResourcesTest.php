<?php

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\InvoiceEmailLog;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\InvoiceReminderLog;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\PropertyAssignment;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionRenewal;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('keeps platform relation resources superadmin-only except for scoped projects access', function () {
    $organization = Organization::factory()->create();

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $project = Project::factory()->for($organization)->create();
    $task = Task::factory()->for($organization)->for($project)->create();

    $taskAssignment = TaskAssignment::factory()->for($task)->create();
    $propertyAssignment = PropertyAssignment::factory()->for($organization)->create();
    $organizationUser = OrganizationUser::factory()->for($organization)->create();
    $tag = Tag::factory()->for($organization)->create();

    $invoiceItem = InvoiceItem::factory()->create();
    $invoicePayment = InvoicePayment::factory()->create();
    $timeEntry = TimeEntry::factory()->create();
    $comment = Comment::factory()->create();
    $commentReaction = CommentReaction::factory()->create();
    $attachment = Attachment::factory()->create();
    $invoiceReminderLog = InvoiceReminderLog::factory()->create();
    $invoiceEmailLog = InvoiceEmailLog::factory()->create();
    $subscriptionPayment = SubscriptionPayment::factory()->create();
    $subscriptionRenewal = SubscriptionRenewal::factory()->create();

    $indexRoutes = [
        'filament.admin.resources.projects.index',
        'filament.admin.resources.tasks.index',
        'filament.admin.resources.task-assignments.index',
        'filament.admin.resources.property-assignments.index',
        'filament.admin.resources.tags.index',
        'filament.admin.resources.invoice-items.index',
        'filament.admin.resources.invoice-payments.index',
        'filament.admin.resources.time-entries.index',
        'filament.admin.resources.comments.index',
        'filament.admin.resources.comment-reactions.index',
        'filament.admin.resources.attachments.index',
        'filament.admin.resources.invoice-reminder-logs.index',
        'filament.admin.resources.invoice-email-logs.index',
        'filament.admin.resources.subscription-payments.index',
        'filament.admin.resources.subscription-renewals.index',
    ];

    $createRoutes = [
        'filament.admin.resources.projects.create',
        'filament.admin.resources.tasks.create',
        'filament.admin.resources.task-assignments.create',
        'filament.admin.resources.property-assignments.create',
        'filament.admin.resources.tags.create',
        'filament.admin.resources.invoice-items.create',
        'filament.admin.resources.invoice-payments.create',
        'filament.admin.resources.time-entries.create',
        'filament.admin.resources.comments.create',
        'filament.admin.resources.comment-reactions.create',
        'filament.admin.resources.attachments.create',
        'filament.admin.resources.invoice-reminder-logs.create',
        'filament.admin.resources.invoice-email-logs.create',
        'filament.admin.resources.subscription-payments.create',
        'filament.admin.resources.subscription-renewals.create',
    ];

    foreach ($indexRoutes as $routeName) {
        actingAs($superadmin);

        get(route($routeName))
            ->assertSuccessful();

        actingAs($admin);

        $response = get(route($routeName));

        if ($routeName === 'filament.admin.resources.projects.index') {
            $response->assertSuccessful();
        } else {
            $response->assertForbidden();
        }
    }

    foreach ($createRoutes as $routeName) {
        actingAs($superadmin);

        get(route($routeName))
            ->assertSuccessful();

        actingAs($admin);

        $response = get(route($routeName));

        if ($routeName === 'filament.admin.resources.projects.create') {
            $response->assertSuccessful();
        } else {
            $response->assertForbidden();
        }
    }

    $viewRoutes = [
        ['name' => 'filament.admin.resources.projects.view', 'record' => $project],
        ['name' => 'filament.admin.resources.tasks.view', 'record' => $task],
        ['name' => 'filament.admin.resources.task-assignments.view', 'record' => $taskAssignment],
        ['name' => 'filament.admin.resources.property-assignments.view', 'record' => $propertyAssignment],
        ['name' => 'filament.admin.resources.tags.view', 'record' => $tag],
        ['name' => 'filament.admin.resources.invoice-items.view', 'record' => $invoiceItem],
        ['name' => 'filament.admin.resources.invoice-payments.view', 'record' => $invoicePayment],
        ['name' => 'filament.admin.resources.time-entries.view', 'record' => $timeEntry],
        ['name' => 'filament.admin.resources.comments.view', 'record' => $comment],
        ['name' => 'filament.admin.resources.comment-reactions.view', 'record' => $commentReaction],
        ['name' => 'filament.admin.resources.attachments.view', 'record' => $attachment],
        ['name' => 'filament.admin.resources.invoice-reminder-logs.view', 'record' => $invoiceReminderLog],
        ['name' => 'filament.admin.resources.invoice-email-logs.view', 'record' => $invoiceEmailLog],
        ['name' => 'filament.admin.resources.subscription-payments.view', 'record' => $subscriptionPayment],
        ['name' => 'filament.admin.resources.subscription-renewals.view', 'record' => $subscriptionRenewal],
    ];

    foreach ($viewRoutes as $viewRoute) {
        actingAs($superadmin);

        get(route($viewRoute['name'], ['record' => $viewRoute['record']]))
            ->assertSuccessful();

        actingAs($admin);

        $response = get(route($viewRoute['name'], ['record' => $viewRoute['record']]));

        if ($viewRoute['name'] === 'filament.admin.resources.projects.view') {
            $response->assertSuccessful();
        } else {
            $response->assertForbidden();
        }
    }

    $editRoutes = [
        ['name' => 'filament.admin.resources.projects.edit', 'record' => $project],
        ['name' => 'filament.admin.resources.tasks.edit', 'record' => $task],
        ['name' => 'filament.admin.resources.task-assignments.edit', 'record' => $taskAssignment],
        ['name' => 'filament.admin.resources.property-assignments.edit', 'record' => $propertyAssignment],
        ['name' => 'filament.admin.resources.tags.edit', 'record' => $tag],
        ['name' => 'filament.admin.resources.invoice-items.edit', 'record' => $invoiceItem],
        ['name' => 'filament.admin.resources.invoice-payments.edit', 'record' => $invoicePayment],
        ['name' => 'filament.admin.resources.time-entries.edit', 'record' => $timeEntry],
        ['name' => 'filament.admin.resources.comments.edit', 'record' => $comment],
        ['name' => 'filament.admin.resources.comment-reactions.edit', 'record' => $commentReaction],
        ['name' => 'filament.admin.resources.attachments.edit', 'record' => $attachment],
        ['name' => 'filament.admin.resources.invoice-reminder-logs.edit', 'record' => $invoiceReminderLog],
        ['name' => 'filament.admin.resources.invoice-email-logs.edit', 'record' => $invoiceEmailLog],
        ['name' => 'filament.admin.resources.subscription-payments.edit', 'record' => $subscriptionPayment],
        ['name' => 'filament.admin.resources.subscription-renewals.edit', 'record' => $subscriptionRenewal],
    ];

    foreach ($editRoutes as $editRoute) {
        actingAs($superadmin);

        get(route($editRoute['name'], ['record' => $editRoute['record']]))
            ->assertSuccessful();

        actingAs($admin);

        $response = get(route($editRoute['name'], ['record' => $editRoute['record']]));

        if ($editRoute['name'] === 'filament.admin.resources.projects.edit') {
            $response->assertSuccessful();
        } else {
            $response->assertForbidden();
        }
    }
});

it('renders contained surface blocks for superadmin relation record pages', function () {
    $organization = Organization::factory()->create();

    $superadmin = User::factory()->superadmin()->create();

    $project = Project::factory()->for($organization)->create();
    $task = Task::factory()->for($organization)->for($project)->create();

    $taskAssignment = TaskAssignment::factory()->for($task)->create();
    $propertyAssignment = PropertyAssignment::factory()->for($organization)->create();
    $organizationUser = OrganizationUser::factory()->for($organization)->create();
    $tag = Tag::factory()->for($organization)->create();

    $invoiceItem = InvoiceItem::factory()->create();
    $invoicePayment = InvoicePayment::factory()->create();
    $timeEntry = TimeEntry::factory()->create();
    $comment = Comment::factory()->create();
    $commentReaction = CommentReaction::factory()->create();
    $attachment = Attachment::factory()->create();
    $invoiceReminderLog = InvoiceReminderLog::factory()->create();
    $invoiceEmailLog = InvoiceEmailLog::factory()->create();
    $subscriptionPayment = SubscriptionPayment::factory()->create();
    $subscriptionRenewal = SubscriptionRenewal::factory()->create();

    actingAs($superadmin);

    $createRoutes = [
        'filament.admin.resources.projects.create',
        'filament.admin.resources.tasks.create',
        'filament.admin.resources.task-assignments.create',
        'filament.admin.resources.property-assignments.create',
        'filament.admin.resources.organization-users.create',
        'filament.admin.resources.tags.create',
        'filament.admin.resources.invoice-items.create',
        'filament.admin.resources.invoice-payments.create',
        'filament.admin.resources.time-entries.create',
        'filament.admin.resources.comments.create',
        'filament.admin.resources.comment-reactions.create',
        'filament.admin.resources.attachments.create',
        'filament.admin.resources.invoice-reminder-logs.create',
        'filament.admin.resources.invoice-email-logs.create',
        'filament.admin.resources.subscription-payments.create',
        'filament.admin.resources.subscription-renewals.create',
    ];

    foreach ($createRoutes as $routeName) {
        get(route($routeName))
            ->assertSuccessful()
            ->assertSee('data-superadmin-surface="true"', false);
    }

    $viewRoutes = [
        ['name' => 'filament.admin.resources.projects.view', 'record' => $project],
        ['name' => 'filament.admin.resources.tasks.view', 'record' => $task],
        ['name' => 'filament.admin.resources.task-assignments.view', 'record' => $taskAssignment],
        ['name' => 'filament.admin.resources.property-assignments.view', 'record' => $propertyAssignment],
        ['name' => 'filament.admin.resources.organization-users.view', 'record' => $organizationUser],
        ['name' => 'filament.admin.resources.tags.view', 'record' => $tag],
        ['name' => 'filament.admin.resources.invoice-items.view', 'record' => $invoiceItem],
        ['name' => 'filament.admin.resources.invoice-payments.view', 'record' => $invoicePayment],
        ['name' => 'filament.admin.resources.time-entries.view', 'record' => $timeEntry],
        ['name' => 'filament.admin.resources.comments.view', 'record' => $comment],
        ['name' => 'filament.admin.resources.comment-reactions.view', 'record' => $commentReaction],
        ['name' => 'filament.admin.resources.attachments.view', 'record' => $attachment],
        ['name' => 'filament.admin.resources.invoice-reminder-logs.view', 'record' => $invoiceReminderLog],
        ['name' => 'filament.admin.resources.invoice-email-logs.view', 'record' => $invoiceEmailLog],
        ['name' => 'filament.admin.resources.subscription-payments.view', 'record' => $subscriptionPayment],
        ['name' => 'filament.admin.resources.subscription-renewals.view', 'record' => $subscriptionRenewal],
    ];

    foreach ($viewRoutes as $viewRoute) {
        get(route($viewRoute['name'], ['record' => $viewRoute['record']]))
            ->assertSuccessful()
            ->assertSee('data-superadmin-surface="true"', false);
    }

    $editRoutes = [
        ['name' => 'filament.admin.resources.projects.edit', 'record' => $project],
        ['name' => 'filament.admin.resources.tasks.edit', 'record' => $task],
        ['name' => 'filament.admin.resources.task-assignments.edit', 'record' => $taskAssignment],
        ['name' => 'filament.admin.resources.property-assignments.edit', 'record' => $propertyAssignment],
        ['name' => 'filament.admin.resources.organization-users.edit', 'record' => $organizationUser],
        ['name' => 'filament.admin.resources.tags.edit', 'record' => $tag],
        ['name' => 'filament.admin.resources.invoice-items.edit', 'record' => $invoiceItem],
        ['name' => 'filament.admin.resources.invoice-payments.edit', 'record' => $invoicePayment],
        ['name' => 'filament.admin.resources.time-entries.edit', 'record' => $timeEntry],
        ['name' => 'filament.admin.resources.comments.edit', 'record' => $comment],
        ['name' => 'filament.admin.resources.comment-reactions.edit', 'record' => $commentReaction],
        ['name' => 'filament.admin.resources.attachments.edit', 'record' => $attachment],
        ['name' => 'filament.admin.resources.invoice-reminder-logs.edit', 'record' => $invoiceReminderLog],
        ['name' => 'filament.admin.resources.invoice-email-logs.edit', 'record' => $invoiceEmailLog],
        ['name' => 'filament.admin.resources.subscription-payments.edit', 'record' => $subscriptionPayment],
        ['name' => 'filament.admin.resources.subscription-renewals.edit', 'record' => $subscriptionRenewal],
    ];

    foreach ($editRoutes as $editRoute) {
        get(route($editRoute['name'], ['record' => $editRoute['record']]))
            ->assertSuccessful()
            ->assertSee('data-superadmin-surface="true"', false);
    }
});
