<?php

use App\Livewire\Shell\NotificationCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createShellNotification(User $user, array $data, ?Carbon $createdAt = null): DatabaseNotification
{
    $createdAt ??= now();

    /** @var DatabaseNotification $notification */
    $notification = DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'shell.test',
        'notifiable_type' => $user::class,
        'notifiable_id' => $user->getKey(),
        'data' => $data,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);

    return $notification;
}

it('renders the unread badge count and only the current users notifications', function () {
    $user = User::factory()->tenant()->create();
    $otherUser = User::factory()->tenant()->create();

    createShellNotification($user, [
        'title' => 'Water reminder',
        'message' => 'Please send your latest meter reading.',
    ], now()->subHours(2));

    createShellNotification($user, [
        'title' => 'Invoice ready',
        'message' => 'Your March invoice is ready to review.',
    ]);

    createShellNotification($otherUser, [
        'title' => 'Hidden notification',
        'message' => 'This should never leak into another account.',
    ]);

    $this->actingAs($user);

    Livewire::test(NotificationCenter::class)
        ->assertSee('data-unread-count="2"', false)
        ->call('togglePanel')
        ->assertSee('Water reminder')
        ->assertSee('Please send your latest meter reading.')
        ->assertSee('2 hours ago')
        ->assertSee('Invoice ready')
        ->assertDontSee('Hidden notification');
});

it('marks a notification as read and redirects when it has a target url', function () {
    $user = User::factory()->tenant()->create();

    $notification = createShellNotification($user, [
        'title' => 'Invoice ready',
        'message' => 'Open your invoice history.',
        'url' => route('tenant.home'),
    ]);

    $this->actingAs($user);

    Livewire::test(NotificationCenter::class)
        ->call('openNotification', $notification->getKey())
        ->assertRedirect(route('tenant.home'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks all notifications as read in one action', function () {
    $user = User::factory()->tenant()->create();

    createShellNotification($user, [
        'title' => 'Reminder one',
        'message' => 'First unread notification.',
    ]);

    createShellNotification($user, [
        'title' => 'Reminder two',
        'message' => 'Second unread notification.',
    ]);

    $this->actingAs($user);

    Livewire::test(NotificationCenter::class)
        ->call('markAllAsRead')
        ->assertSet('unreadCount', 0);

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('shows only the most recent notifications in the panel', function () {
    $user = User::factory()->tenant()->create();

    foreach (range(1, 25) as $index) {
        createShellNotification($user, [
            'title' => sprintf('Notification-%02d', $index),
            'message' => sprintf('Notification body %02d', $index),
        ], now()->subMinutes(26 - $index));
    }

    $this->actingAs($user);

    Livewire::test(NotificationCenter::class)
        ->call('togglePanel')
        ->assertSee('Notification-25')
        ->assertSee('Notification-06')
        ->assertDontSee('Notification-05')
        ->assertDontSee('Notification-01');
});
