<?php

use App\Filament\Actions\Superadmin\Organizations\SendOrganizationNotificationAction;
use App\Jobs\Superadmin\Organizations\SendOrganizationAnnouncementJob;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\Superadmin\OrganizationBroadcastNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues organization announcements instead of sending them inline', function () {
    Queue::fake();
    Notification::fake();

    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'email' => 'owner@northwind.test',
    ]);

    User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'email' => 'manager@northwind.test',
    ]);

    app(SendOrganizationNotificationAction::class)->handle(
        $organization,
        'Water Shutdown',
        'Water service will be offline tomorrow from 08:00 to 10:00.',
        'warning',
    );

    Queue::assertPushed(SendOrganizationAnnouncementJob::class, function (SendOrganizationAnnouncementJob $job) use ($organization): bool {
        return $job->organizationId === $organization->id
            && $job->title === 'Water Shutdown'
            && $job->body === 'Water service will be offline tomorrow from 08:00 to 10:00.'
            && $job->severity === 'warning';
    });

    Notification::assertNothingSent();
});

it('delivers queued organization announcements only to users in the organization', function () {
    Notification::fake();

    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Olivia Owner',
        'email' => 'owner@northwind.test',
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'name' => 'Maya Manager',
        'email' => 'manager@northwind.test',
    ]);

    $outsider = User::factory()->admin()->create([
        'organization_id' => Organization::factory()->create()->id,
        'name' => 'Owen Outsider',
        'email' => 'outsider@example.test',
    ]);

    $job = new SendOrganizationAnnouncementJob(
        $organization->id,
        'Water Shutdown',
        'Water service will be offline tomorrow from 08:00 to 10:00.',
        'warning',
    );

    $job->handle();

    $assertNotification = function (OrganizationBroadcastNotification $notification, array $channels, object $notifiable) use ($organization): bool {
        return $channels === ['database']
            && $notification->toArray($notifiable)['organization_id'] === $organization->id
            && $notification->toArray($notifiable)['organization_name'] === $organization->name
            && $notification->toArray($notifiable)['title'] === 'Water Shutdown'
            && $notification->toArray($notifiable)['severity'] === 'warning';
    };

    Notification::assertSentTo($owner, OrganizationBroadcastNotification::class, $assertNotification);
    Notification::assertSentTo($manager, OrganizationBroadcastNotification::class, $assertNotification);
    Notification::assertNotSentTo($outsider, OrganizationBroadcastNotification::class);
});
