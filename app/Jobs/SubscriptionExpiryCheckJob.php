<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Subscription;
use App\Models\Organization;
use App\Services\SubscriptionAutomationService;
use Carbon\Carbon;

/**
 * Job for checking subscription expiry and processing notifications/renewals
 * 
 * Runs daily to check for expiring subscriptions, send notifications,
 * and process auto-renewals according to configuration
 */
class SubscriptionExpiryCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('subscriptions');
    }

    /**
     * Execute the job.
     */
    public function handle(SubscriptionAutomationService $automationService): void
    {
        Log::info('Starting subscription expiry check');

        $stats = [
            'notifications_sent' => 0,
            'auto_renewals_processed' => 0,
            'auto_renewal_failures' => 0,
            'expired_subscriptions' => 0,
        ];

        try {
            // Check for subscriptions expiring in 30, 14, and 7 days
            $stats['notifications_sent'] += $this->sendExpiryNotifications(30);
            $stats['notifications_sent'] += $this->sendExpiryNotifications(14);
            $stats['notifications_sent'] += $this->sendExpiryNotifications(7);

            // Process auto-renewals for subscriptions expiring today
            $autoRenewalResults = $this->processAutoRenewals($automationService);
            $stats['auto_renewals_processed'] = $autoRenewalResults['success'];
            $stats['auto_renewal_failures'] = $autoRenewalResults['failures'];

            // Mark expired subscriptions
            $stats['expired_subscriptions'] = $this->markExpiredSubscriptions();

            Log::info('Subscription expiry check completed', $stats);

        } catch (\Exception $e) {
            Log::error('Subscription expiry check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Send expiry notifications for subscriptions expiring in X days
     */
    private function sendExpiryNotifications(int $days): int
    {
        $targetDate = Carbon::now()->addDays($days)->toDateString();
        
        $subscriptions = Subscription::query()
            ->with(['user.organization'])
            ->where('status', 'active')
            ->whereDate('expires_at', $targetDate)
            ->get();

        $notificationsSent = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $this->sendExpiryNotification($subscription, $days);
                $notificationsSent++;
            } catch (\Exception $e) {
                Log::error('Failed to send expiry notification', [
                    'subscription_id' => $subscription->id,
                    'days' => $days,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Sent {$notificationsSent} expiry notifications for {$days} days", [
            'days' => $days,
            'notifications_sent' => $notificationsSent,
            'total_expiring' => $subscriptions->count(),
        ]);

        return $notificationsSent;
    }

    /**
     * Send individual expiry notification
     */
    private function sendExpiryNotification(Subscription $subscription, int $days): void
    {
        $organization = $subscription->user->organization ?? null;
        
        if (!$organization || !$subscription->user->email) {
            Log::warning('Cannot send expiry notification - missing organization or email', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);
            return;
        }

        // Simple email notification (in production, you'd create a proper Mailable)
        Mail::raw(
            "Your subscription for {$organization->name} will expire in {$days} days.\n\n" .
            "Subscription Details:\n" .
            "- Plan: {$subscription->plan_type}\n" .
            "- Expires: {$subscription->expires_at->format('Y-m-d')}\n\n" .
            "Please renew your subscription to continue using our services.",
            function ($message) use ($subscription, $organization, $days) {
                $message->to($subscription->user->email)
                       ->subject("Subscription Expiring in {$days} Days - {$organization->name}");
            }
        );

        Log::debug('Expiry notification sent', [
            'subscription_id' => $subscription->id,
            'organization' => $organization->name,
            'email' => $subscription->user->email,
            'days' => $days,
        ]);
    }

    /**
     * Process auto-renewals for subscriptions expiring today
     */
    private function processAutoRenewals(SubscriptionAutomationService $automationService): array
    {
        $expiringToday = Subscription::query()
            ->with(['user.organization'])
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->whereDate('expires_at', Carbon::now()->toDateString())
            ->get();

        $success = 0;
        $failures = 0;

        foreach ($expiringToday as $subscription) {
            try {
                // Attempt auto-renewal
                $renewalPeriod = $subscription->renewal_period ?? 365; // Default to 1 year
                $newExpiryDate = $subscription->expires_at->addDays($renewalPeriod);
                
                $subscription->renew($newExpiryDate);
                
                // Log the renewal
                Log::info('Auto-renewal processed', [
                    'subscription_id' => $subscription->id,
                    'organization' => $subscription->user->organization?->name,
                    'new_expiry_date' => $newExpiryDate->toDateString(),
                ]);

                $success++;

            } catch (\Exception $e) {
                Log::error('Auto-renewal failed', [
                    'subscription_id' => $subscription->id,
                    'organization' => $subscription->user->organization?->name,
                    'error' => $e->getMessage(),
                ]);

                // Send failure notification to superadmins
                $this->sendAutoRenewalFailureNotification($subscription, $e->getMessage());
                
                $failures++;
            }
        }

        return [
            'success' => $success,
            'failures' => $failures,
        ];
    }

    /**
     * Mark subscriptions as expired
     */
    private function markExpiredSubscriptions(): int
    {
        $expiredCount = Subscription::query()
            ->where('status', 'active')
            ->where('expires_at', '<', Carbon::now())
            ->update(['status' => 'expired']);

        if ($expiredCount > 0) {
            Log::info("Marked {$expiredCount} subscriptions as expired");
        }

        return $expiredCount;
    }

    /**
     * Send auto-renewal failure notification to superadmins
     */
    private function sendAutoRenewalFailureNotification(Subscription $subscription, string $error): void
    {
        try {
            // Get superadmin emails (simplified - in production you'd have a proper notification system)
            $superadminEmails = \App\Models\User::where('role', 'superadmin')
                ->pluck('email')
                ->toArray();

            if (empty($superadminEmails)) {
                return;
            }

            $organization = $subscription->user->organization;
            
            Mail::raw(
                "Auto-renewal failed for subscription ID {$subscription->id}\n\n" .
                "Organization: {$organization?->name}\n" .
                "Plan: {$subscription->plan_type}\n" .
                "Original Expiry: {$subscription->expires_at->format('Y-m-d')}\n" .
                "Error: {$error}\n\n" .
                "Please review and process manually.",
                function ($message) use ($superadminEmails, $subscription) {
                    $message->to($superadminEmails)
                           ->subject("Auto-Renewal Failed - Subscription {$subscription->id}");
                }
            );

        } catch (\Exception $e) {
            Log::error('Failed to send auto-renewal failure notification', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Subscription expiry check job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}