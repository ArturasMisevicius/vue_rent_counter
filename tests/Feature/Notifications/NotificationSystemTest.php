<?php

declare(strict_types=1);

use App\Filament\Actions\Admin\Invoices\SendInvoiceReminderAction;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\User;
use App\Notifications\InvoiceOverdueReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

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
