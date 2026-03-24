<?php

use App\Livewire\Shell\NotificationCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders unread counts and notification preview data for the authenticated user', function () {
    $user = User::factory()->admin()->create();

    $user->notify(shellNotification([
        'title' => 'Invoice ready',
        'body' => 'Invoice INV-2026-001 is ready for review.',
    ]));

    $user->notify(shellNotification([
        'title' => 'Reading approved',
        'body' => 'Reading for Apartment 12A has been approved.',
    ]));

    Livewire::actingAs($user)
        ->test(NotificationCenter::class)
        ->assertSee('2')
        ->assertSee('Invoice ready')
        ->assertSee('Invoice INV-2026-001 is ready for review.')
        ->assertSee('Reading approved');
});

it('marks a single notification as read when opened', function () {
    $user = User::factory()->manager()->create();

    $user->notify(shellNotification([
        'title' => 'Usage warning',
        'body' => 'Your organization is close to the monthly limit.',
    ]));

    $notificationId = $user->notifications()->firstOrFail()->id;

    Livewire::actingAs($user)
        ->test(NotificationCenter::class)
        ->call('openNotification', $notificationId);

    expect($user->notifications()->firstOrFail()->read())->toBeTrue();
});

it('marks all unread notifications as read in one action', function () {
    $user = User::factory()->tenant()->create();

    $user->notify(shellNotification([
        'title' => 'Lease update',
        'body' => 'Your lease details were updated.',
    ]));

    $user->notify(shellNotification([
        'title' => 'Payment reminder',
        'body' => 'Your invoice is due tomorrow.',
    ]));

    Livewire::actingAs($user)
        ->test(NotificationCenter::class)
        ->call('markAllAsRead')
        ->assertSet('unreadCount', 0);

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('never renders another users notifications', function () {
    $user = User::factory()->admin()->create();
    $otherUser = User::factory()->tenant()->create();

    $user->notify(shellNotification([
        'title' => 'Visible notification',
        'body' => 'Only the signed-in user should see this.',
    ]));

    $otherUser->notify(shellNotification([
        'title' => 'Hidden notification',
        'body' => 'This should stay isolated.',
    ]));

    Livewire::actingAs($user)
        ->test(NotificationCenter::class)
        ->assertSee('Visible notification')
        ->assertDontSee('Hidden notification');
});

it('refreshes translated notification center copy when the shell locale changes', function () {
    $user = User::factory()->admin()->create();

    $user->notify(shellNotification([
        'title' => 'Invoice ready',
        'body' => 'Invoice INV-2026-001 is ready for review.',
    ]));

    $component = Livewire::actingAs($user)
        ->test(NotificationCenter::class)
        ->assertSeeText(__('shell.notifications.heading', [], 'en'))
        ->assertSeeText(__('shell.notifications.actions.mark_all_read', [], 'en'));

    $user->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($user->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('shell.notifications.heading', [], 'lt'))
        ->assertSeeText(__('shell.notifications.actions.mark_all_read', [], 'lt'));
});

function shellNotification(array $data): Notification
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
