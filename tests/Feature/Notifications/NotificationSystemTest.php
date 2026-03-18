<?php

declare(strict_types=1);

use App\Enums\PlatformNotificationSeverity;
use App\Filament\Actions\Admin\Invoices\SendInvoiceReminderAction;
use App\Filament\Actions\Superadmin\Organizations\SendOrganizationNotificationAction;
use App\Livewire\NotificationBell;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationRecipient;
use App\Models\User;
use App\Notifications\InvoiceOverdueReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('superadmin sending a notification creates platform notification and recipient records', function (): void {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();

    User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'email' => 'admin@northwind.test',
    ]);

    User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'email' => 'manager@northwind.test',
    ]);

    $this->actingAs($superadmin);

    $notification = app(SendOrganizationNotificationAction::class)->handle($organization, [
        'title' => 'Scheduled maintenance',
        'body' => 'The billing workspace will be briefly unavailable after midnight.',
        'severity' => PlatformNotificationSeverity::WARNING,
    ]);

    expect(PlatformNotification::query()->whereKey($notification->id)->exists())->toBeTrue()
        ->and(PlatformNotificationRecipient::query()->where('platform_notification_id', $notification->id)->count())->toBe(1)
        ->and(
            PlatformNotificationRecipient::query()
                ->where('platform_notification_id', $notification->id)
                ->value('organization_id'),
        )->toBe($organization->id);
});

it('notification bell shows the correct unread count', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $latestNotification = PlatformNotification::factory()->create([
        'title' => 'Billing reminder',
        'body' => 'A new billing cycle has started.',
    ]);

    PlatformNotificationRecipient::factory()
        ->for($latestNotification, 'notification')
        ->for($organization)
        ->create([
            'delivery_status' => 'sent',
            'sent_at' => now()->subMinutes(5),
            'read_at' => null,
        ]);

    $otherOrganization = Organization::factory()->create();
    $foreignNotification = PlatformNotification::factory()->create([
        'title' => 'Foreign alert',
        'body' => 'This should stay hidden.',
    ]);

    PlatformNotificationRecipient::factory()
        ->for($foreignNotification, 'notification')
        ->for($otherOrganization)
        ->create([
            'delivery_status' => 'sent',
            'sent_at' => now()->subMinute(),
            'read_at' => null,
        ]);

    Livewire::actingAs($admin)
        ->test(NotificationBell::class)
        ->assertSet('unreadCount', 1)
        ->assertSee('Billing reminder')
        ->assertDontSee('Foreign alert');
});

it('marking a notification as read decrements the unread count', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $notification = PlatformNotification::factory()->create([
        'title' => 'Policy update',
        'body' => 'Please review the latest notification settings.',
    ]);

    $recipient = PlatformNotificationRecipient::factory()
        ->for($notification, 'notification')
        ->for($organization)
        ->create([
            'delivery_status' => 'sent',
            'sent_at' => now()->subMinutes(2),
            'read_at' => null,
        ]);

    Livewire::actingAs($admin)
        ->test(NotificationBell::class)
        ->assertSet('unreadCount', 1)
        ->call('trackNotification', $recipient->id)
        ->assertSet('unreadCount', 0);

    expect($recipient->fresh()->read_at)->not()->toBeNull();
});

it('invoice overdue notification email contains the correct amount and a valid pdf download link', function (): void {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'email' => 'tenant@example.test',
    ]);

    OrganizationSetting::factory()->for($organization)->create([
        'notification_preferences' => [
            'new_invoice_generated' => false,
            'invoice_overdue' => true,
            'tenant_submits_reading' => false,
            'subscription_expiring' => false,
        ],
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-OVERDUE-777',
            'currency' => 'EUR',
            'total_amount' => 99.50,
            'amount_paid' => 22.00,
            'paid_amount' => 22.00,
            'billing_period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'due_date' => now()->subDays(9)->toDateString(),
        ]);

    $log = app(SendInvoiceReminderAction::class)->handle($invoice, $admin);

    expect($log)->not()->toBeNull()
        ->and($invoice->fresh()->last_reminder_sent_at)->not()->toBeNull();

    Notification::assertSentOnDemand(InvoiceOverdueReminderNotification::class, function (
        InvoiceOverdueReminderNotification $notification,
        array $channels,
        AnonymousNotifiable $notifiable,
    ) use ($invoice): bool {
        $mailMessage = $notification->toMail($notifiable);

        return $channels === ['mail']
            && $mailMessage->subject === __('admin.reports.notifications.overdue_subject', ['number' => $invoice->invoice_number])
            && in_array(__('admin.reports.notifications.overdue_balance', [
                'amount' => sprintf(
                    '%s %s',
                    $invoice->currency,
                    number_format($invoice->outstanding_balance, 2, '.', ''),
                ),
            ]), $mailMessage->introLines, true)
            && $mailMessage->actionUrl === route('tenant.invoices.download', $invoice);
    });
});

it('email notifications are not sent when the admin has disabled the relevant preference', function (): void {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'email' => 'tenant@example.test',
    ]);

    OrganizationSetting::factory()->for($organization)->create([
        'notification_preferences' => [
            'new_invoice_generated' => false,
            'invoice_overdue' => false,
            'tenant_submits_reading' => false,
            'subscription_expiring' => false,
        ],
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($tenant, 'tenant')
        ->create([
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

    $result = app(SendInvoiceReminderAction::class)->handle($invoice, $admin);

    expect($result)->toBeNull()
        ->and($invoice->fresh()->last_reminder_sent_at)->toBeNull();

    Notification::assertNothingSent();
});
