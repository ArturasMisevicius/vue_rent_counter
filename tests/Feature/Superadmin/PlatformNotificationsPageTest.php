<?php

use App\Filament\Pages\PlatformNotifications;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows the platform notifications feed only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $superadmin->notify(platformShellNotification([
        'title' => 'Invoice ready',
        'body' => 'Invoice INV-2026-044 is ready for review.',
        'url' => '/app/invoices',
    ]));

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-notifications'))
        ->assertSuccessful()
        ->assertSeeText('Platform Notifications')
        ->assertSeeText('Unread Notifications')
        ->assertSeeText('Invoice ready')
        ->assertSeeText('Invoice INV-2026-044 is ready for review.')
        ->assertSeeText('Unread')
        ->assertSeeText('Mark All Read')
        ->assertSeeText('Open');

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.platform-notifications'))
        ->assertForbidden();
});

it('keeps the platform notifications feed scoped to the authenticated superadmin', function () {
    $superadmin = User::factory()->superadmin()->create();
    $otherSuperadmin = User::factory()->superadmin()->create();

    $superadmin->notify(platformShellNotification([
        'title' => 'Visible notification',
        'body' => 'Only the active superadmin should see this notification.',
    ]));

    $otherSuperadmin->notify(platformShellNotification([
        'title' => 'Hidden notification',
        'body' => 'This should not leak across superadmin accounts.',
    ]));

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-notifications'))
        ->assertSuccessful()
        ->assertSeeText('Visible notification')
        ->assertDontSeeText('Hidden notification');
});

it('marks notifications as read and redirects when opening a platform notification', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $superadmin->notify(platformShellNotification([
        'title' => 'Open me',
        'body' => 'This notification should mark as read and redirect.',
        'url' => '/app/platform-dashboard',
    ]));

    $notificationId = $superadmin->notifications()->firstOrFail()->id;

    Livewire::test(PlatformNotifications::class)
        ->call('openNotification', $notificationId)
        ->assertRedirect('/app/platform-dashboard');

    expect($superadmin->notifications()->firstOrFail()->read())->toBeTrue();
});

it('marks all notifications as read from the platform notifications page', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $superadmin->notify(platformShellNotification([
        'title' => 'First unread',
        'body' => 'First unread notification.',
    ]));

    $superadmin->notify(platformShellNotification([
        'title' => 'Second unread',
        'body' => 'Second unread notification.',
    ]));

    Livewire::test(PlatformNotifications::class)
        ->call('markAllAsRead')
        ->assertSet('statusMessage', 'All notifications marked as read.');

    expect($superadmin->fresh()->unreadNotifications()->count())->toBe(0);
});

function platformShellNotification(array $data): Notification
{
    return new class($data) extends Notification
    {
        public function __construct(
            protected array $data,
        ) {}

        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function toDatabase(object $notifiable): array
        {
            return array_merge([
                'title' => 'Notification',
                'body' => 'Notification body',
            ], $this->data);
        }
    };
}
