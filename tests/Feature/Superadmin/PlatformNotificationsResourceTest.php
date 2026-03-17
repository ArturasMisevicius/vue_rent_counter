<?php

use App\Actions\Superadmin\Notifications\SavePlatformNotificationDraftAction;
use App\Actions\Superadmin\Notifications\SendPlatformNotificationAction;
use App\Enums\PlatformNotificationSeverity;
use App\Enums\PlatformNotificationStatus;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows platform notification resource pages only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $notification = PlatformNotification::factory()->create([
        'title' => 'Billing reminder',
    ]);

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.platform-notifications.index'))
        ->assertSuccessful()
        ->assertSeeText('Platform Notifications')
        ->assertSeeText($notification->title);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.platform-notifications.create'))
        ->assertSuccessful()
        ->assertSeeText('Title')
        ->assertSeeText('Message')
        ->assertSeeText('Severity');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.platform-notifications.view', $notification))
        ->assertSuccessful()
        ->assertSeeText($notification->title);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.platform-notifications.edit', $notification))
        ->assertSuccessful()
        ->assertSeeText('Save changes');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.platform-notifications.index'))
        ->assertForbidden();
});

it('saves drafts and fans notifications out to active users', function () {
    $superadmin = User::factory()->superadmin()->create();
    User::factory()->admin()->create([
        'status' => UserStatus::ACTIVE,
    ]);
    User::factory()->manager()->create([
        'status' => UserStatus::ACTIVE,
    ]);

    $draft = app(SavePlatformNotificationDraftAction::class)->handle([
        'title' => 'Scheduled maintenance',
        'body' => 'Platform access will be limited after midnight.',
        'severity' => PlatformNotificationSeverity::WARNING,
    ]);

    $sent = app(SendPlatformNotificationAction::class)->handle($draft);

    expect($draft->status)->toBe(PlatformNotificationStatus::DRAFT)
        ->and($sent->fresh()->status)->toBe(PlatformNotificationStatus::SENT)
        ->and($sent->fresh()->deliveries()->count())->toBe(
            User::query()->where('status', UserStatus::ACTIVE)->count(),
        );
});
