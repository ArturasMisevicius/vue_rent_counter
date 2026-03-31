<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Filament\Actions\Admin\Invoices\SendInvoiceReminderAction;
use App\Jobs\SendInvoiceReminderJob;
use App\Models\Invoice;
use App\Models\InvoiceReminderLog;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use App\Notifications\InvoiceOverdueReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('queues overdue invoice reminders and the queued job sends the correct email payload', function (): void {
    Queue::fake();
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
            'status' => InvoiceStatus::OVERDUE,
            'total_amount' => 99.50,
            'amount_paid' => 22.00,
            'paid_amount' => 22.00,
            'billing_period_start' => now()->startOfMonth()->subMonth()->toDateString(),
            'billing_period_end' => now()->startOfMonth()->subDay()->toDateString(),
            'due_date' => now()->subDays(9)->toDateString(),
        ]);

    $queued = app(SendInvoiceReminderAction::class)->handle($invoice, $admin);

    expect($queued)->toBeTrue()
        ->and($invoice->fresh()->last_reminder_sent_at)->toBeNull()
        ->and(InvoiceReminderLog::query()->count())->toBe(0);

    Queue::assertPushed(SendInvoiceReminderJob::class, fn (SendInvoiceReminderJob $job): bool => $job->invoiceId === $invoice->id
        && $job->actorId === $admin->id
        && $job->recipientEmail === 'tenant@example.test');

    (new SendInvoiceReminderJob($invoice->id, $admin->id, 'tenant@example.test'))->handle();

    expect($invoice->fresh()->last_reminder_sent_at)->not()->toBeNull()
        ->and(InvoiceReminderLog::query()->count())->toBe(1);

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

it('does not queue overdue reminders when the admin has disabled the relevant preference', function (): void {
    Queue::fake();
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
            'status' => InvoiceStatus::OVERDUE,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

    $result = app(SendInvoiceReminderAction::class)->handle($invoice, $admin);

    expect($result)->toBeFalse()
        ->and($invoice->fresh()->last_reminder_sent_at)->toBeNull();

    Queue::assertNothingPushed();
    Notification::assertNothingSent();
});
