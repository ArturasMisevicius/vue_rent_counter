<?php

use App\Console\Commands\NotifyOverdueInvoicesCommand;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Notifications\OverdueInvoiceNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

it('sends overdue notification and stamps notified_at', function () {
    Notification::fake();

    $tenant = Tenant::factory()->create(['email' => 'tenant@test.com']);
    $invoice = Invoice::factory()
        ->forTenantRenter($tenant)
        ->state([
            'status' => InvoiceStatus::FINALIZED,
            'due_date' => now()->subDay(),
            'paid_at' => null,
            'overdue_notified_at' => null,
        ])
        ->create();

    Artisan::call(NotifyOverdueInvoicesCommand::class);

    Notification::assertSentOnDemand(
        OverdueInvoiceNotification::class,
        function ($notification, $channels, $notifiable) use ($invoice) {
            return $notifiable->routes['mail'] === 'tenant@test.com'
                && $notification->invoice->is($invoice);
        }
    );

    expect($invoice->fresh()->overdue_notified_at)->not()->toBeNull();
});

it('does not notify when invoice is paid', function () {
    Notification::fake();

    $tenant = Tenant::factory()->create(['email' => 'tenant@test.com']);
    Invoice::factory()
        ->forTenantRenter($tenant)
        ->state([
            'status' => InvoiceStatus::PAID,
            'due_date' => now()->subDays(2),
            'paid_at' => now()->subDay(),
            'overdue_notified_at' => null,
        ])
        ->create();

    Artisan::call(NotifyOverdueInvoicesCommand::class);

    Notification::assertNothingSent();
});
