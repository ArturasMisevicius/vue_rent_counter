<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\OverdueInvoiceNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:notify-overdue';

    protected $description = 'Send email notifications for overdue invoices to tenants';

    public function handle(): int
    {
        $today = now()->startOfDay();

        $overdueInvoices = Invoice::query()
            ->whereNotNull('due_date')
            ->whereNull('paid_at')
            ->whereDate('due_date', '<', $today)
            ->whereNull('overdue_notified_at')
            ->with('tenant')
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('No overdue invoices to notify.');
            return self::SUCCESS;
        }

        $this->info("Sending notifications for {$overdueInvoices->count()} overdue invoice(s).");

        $overdueInvoices->each(function (Invoice $invoice) {
            if (!$invoice->tenant || empty($invoice->tenant->email)) {
                return;
            }

            Notification::route('mail', $invoice->tenant->email)
                ->notify(new OverdueInvoiceNotification($invoice));

            // Bypass invoice immutability for notification timestamp
            Invoice::withoutEvents(function () use ($invoice) {
                $invoice->forceFill([
                    'overdue_notified_at' => now(),
                ])->save();
            });
        });

        $this->info('Notifications dispatched.');

        return self::SUCCESS;
    }
}
