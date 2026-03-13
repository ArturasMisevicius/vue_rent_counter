<?php

namespace App\Console\Commands;

use App\Services\SubscriptionAutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:monitor 
                            {--notifications-only : Only process expiry notifications}
                            {--renewals-only : Only process auto-renewals}
                            {--dry-run : Show what would be processed without taking action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor subscriptions for expiry notifications and auto-renewals';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionAutomationService $automationService): int
    {
        $this->info('Starting subscription monitoring...');
        
        $dryRun = $this->option('dry-run');
        $notificationsOnly = $this->option('notifications-only');
        $renewalsOnly = $this->option('renewals-only');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No actual notifications or renewals will be processed');
        }

        $totalNotifications = 0;
        $totalRenewals = 0;
        $totalErrors = 0;

        // Process expiry notifications unless renewals-only is specified
        if (!$renewalsOnly) {
            $this->info('Processing expiry notifications...');
            
            if (!$dryRun) {
                $notificationSummary = $automationService->processExpiryNotifications();
                $totalNotifications = $notificationSummary['notifications_sent'];
                $totalErrors += count($notificationSummary['errors']);

                $this->displayNotificationSummary($notificationSummary);
            } else {
                $this->info('DRY RUN: Would check for subscriptions expiring in 30, 14, and 7 days');
            }
        }

        // Process auto-renewals unless notifications-only is specified
        if (!$notificationsOnly) {
            $this->info('Processing auto-renewals...');
            
            if (!$dryRun) {
                $renewalSummary = $automationService->processAutoRenewals();
                $totalRenewals = $renewalSummary['renewals_processed'];
                $totalErrors += count($renewalSummary['failures']);

                $this->displayRenewalSummary($renewalSummary);
            } else {
                $this->info('DRY RUN: Would check for subscriptions eligible for auto-renewal');
            }
        }

        // Display final summary
        $this->newLine();
        $this->info('=== MONITORING COMPLETE ===');
        
        if (!$dryRun) {
            $this->line("Notifications sent: {$totalNotifications}");
            $this->line("Renewals processed: {$totalRenewals}");
            
            if ($totalErrors > 0) {
                $this->error("Errors encountered: {$totalErrors}");
                $this->warn('Check the application logs for detailed error information.');
            } else {
                $this->info('No errors encountered.');
            }

            // Log the monitoring run
            Log::info('Subscription monitoring completed', [
                'notifications_sent' => $totalNotifications,
                'renewals_processed' => $totalRenewals,
                'errors' => $totalErrors,
                'command_options' => [
                    'notifications_only' => $notificationsOnly,
                    'renewals_only' => $renewalsOnly,
                    'dry_run' => $dryRun
                ]
            ]);
        }

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Display notification processing summary.
     */
    protected function displayNotificationSummary(array $summary): void
    {
        $this->newLine();
        $this->info('--- Notification Summary ---');
        $this->line("Total notifications sent: {$summary['notifications_sent']}");

        if (!empty($summary['processed_subscriptions'])) {
            $this->line('Notifications sent to:');
            foreach ($summary['processed_subscriptions'] as $subscription) {
                $this->line("  • {$subscription['organization']} (expires in {$subscription['days_until_expiry']} days)");
            }
        }

        if (!empty($summary['errors'])) {
            $this->error('Notification errors:');
            foreach ($summary['errors'] as $error) {
                $this->line("  • {$error}");
            }
        }
    }

    /**
     * Display renewal processing summary.
     */
    protected function displayRenewalSummary(array $summary): void
    {
        $this->newLine();
        $this->info('--- Renewal Summary ---');
        $this->line("Total renewals processed: {$summary['renewals_processed']}");

        if (!empty($summary['processed_subscriptions'])) {
            $this->line('Successful renewals:');
            foreach ($summary['processed_subscriptions'] as $subscription) {
                $this->line("  • {$subscription['organization']} (new expiry: {$subscription['new_expiry_date']})");
            }
        }

        if (!empty($summary['failures'])) {
            $this->error('Renewal failures:');
            foreach ($summary['failures'] as $failure) {
                $this->line("  • {$failure['organization']}: {$failure['error']}");
            }
        }
    }
}
