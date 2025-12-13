<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionExpiryWarningEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * SubscriptionAutomationService
 * 
 * Handles automated subscription management including:
 * - Expiry notification scheduling (30, 14, 7 days)
 * - Auto-renewal configuration and execution
 * - Renewal failure handling
 * 
 * Requirements: 8.1, 8.2, 8.3, 8.4
 */
class SubscriptionAutomationService
{
    /**
     * Notification intervals in days before expiry
     */
    const NOTIFICATION_INTERVALS = [30, 14, 7];

    /**
     * Check for subscriptions that need expiry notifications and send them.
     * 
     * @return array Summary of notifications sent
     */
    public function processExpiryNotifications(): array
    {
        $summary = [
            'notifications_sent' => 0,
            'errors' => [],
            'processed_subscriptions' => []
        ];

        foreach (self::NOTIFICATION_INTERVALS as $days) {
            $subscriptions = $this->getSubscriptionsExpiringIn($days);
            
            foreach ($subscriptions as $subscription) {
                try {
                    if ($this->shouldSendNotification($subscription, $days)) {
                        $this->sendExpiryNotification($subscription, $days);
                        $summary['notifications_sent']++;
                        $summary['processed_subscriptions'][] = [
                            'subscription_id' => $subscription->id,
                            'organization' => $subscription->user->organization_name ?? $subscription->user->name,
                            'days_until_expiry' => $days,
                            'status' => 'sent'
                        ];
                    }
                } catch (\Exception $e) {
                    $error = "Failed to send notification for subscription {$subscription->id}: " . $e->getMessage();
                    Log::error($error, [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->user_id,
                        'exception' => $e
                    ]);
                    $summary['errors'][] = $error;
                }
            }
        }

        return $summary;
    }

    /**
     * Process auto-renewals for eligible subscriptions.
     * 
     * @return array Summary of renewals processed
     */
    public function processAutoRenewals(): array
    {
        $summary = [
            'renewals_processed' => 0,
            'failures' => [],
            'processed_subscriptions' => []
        ];

        $subscriptions = $this->getSubscriptionsForAutoRenewal();

        foreach ($subscriptions as $subscription) {
            try {
                $this->executeAutoRenewal($subscription);
                $summary['renewals_processed']++;
                $summary['processed_subscriptions'][] = [
                    'subscription_id' => $subscription->id,
                    'organization' => $subscription->user->organization_name ?? $subscription->user->name,
                    'new_expiry_date' => $subscription->fresh()->expires_at->format('Y-m-d'),
                    'status' => 'renewed'
                ];
            } catch (\Exception $e) {
                $error = "Auto-renewal failed for subscription {$subscription->id}: " . $e->getMessage();
                Log::error($error, [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'exception' => $e
                ]);
                
                $this->handleRenewalFailure($subscription, $e->getMessage());
                $summary['failures'][] = [
                    'subscription_id' => $subscription->id,
                    'organization' => $subscription->user->organization_name ?? $subscription->user->name,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $summary;
    }

    /**
     * Configure auto-renewal settings for a subscription.
     * 
     * @param Subscription $subscription
     * @param bool $autoRenew
     * @param string $renewalPeriod monthly, quarterly, annually
     * @return void
     */
    public function configureAutoRenewal(Subscription $subscription, bool $autoRenew, string $renewalPeriod = 'annually'): void
    {
        // Add auto_renew and renewal_period columns to subscriptions table if they don't exist
        // For now, we'll store this in a JSON column or create a separate table
        $subscription->update([
            'auto_renew' => $autoRenew,
            'renewal_period' => $renewalPeriod
        ]);

        Log::info("Auto-renewal configured for subscription {$subscription->id}", [
            'subscription_id' => $subscription->id,
            'auto_renew' => $autoRenew,
            'renewal_period' => $renewalPeriod,
            'user_id' => $subscription->user_id
        ]);
    }

    /**
     * Get subscriptions expiring in the specified number of days.
     * 
     * @param int $days
     * @return Collection<Subscription>
     */
    protected function getSubscriptionsExpiringIn(int $days): Collection
    {
        $targetDate = now()->addDays($days)->startOfDay();
        $endDate = $targetDate->copy()->endOfDay();

        return Subscription::with('user')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->whereBetween('expires_at', [$targetDate, $endDate])
            ->get();
    }

    /**
     * Check if a notification should be sent for this subscription and interval.
     * Prevents duplicate notifications.
     * 
     * @param Subscription $subscription
     * @param int $days
     * @return bool
     */
    protected function shouldSendNotification(Subscription $subscription, int $days): bool
    {
        // Check if we've already sent a notification for this interval
        // This could be tracked in a separate table or using cache
        $cacheKey = "subscription_notification_{$subscription->id}_{$days}";
        
        if (cache()->has($cacheKey)) {
            return false;
        }

        // Set cache to prevent duplicate notifications (expires after the interval + 1 day)
        cache()->put($cacheKey, true, now()->addDays($days + 1));
        
        return true;
    }

    /**
     * Send expiry notification for a subscription.
     * 
     * @param Subscription $subscription
     * @param int $daysUntilExpiry
     * @return void
     */
    protected function sendExpiryNotification(Subscription $subscription, int $daysUntilExpiry): void
    {
        $subscription->user->notify(new SubscriptionExpiryWarningEmail($subscription));

        Log::info("Expiry notification sent for subscription {$subscription->id}", [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'days_until_expiry' => $daysUntilExpiry,
            'expires_at' => $subscription->expires_at->toDateTimeString()
        ]);
    }

    /**
     * Get subscriptions eligible for auto-renewal.
     * 
     * @return Collection<Subscription>
     */
    protected function getSubscriptionsForAutoRenewal(): Collection
    {
        // Get subscriptions that expire today and have auto-renewal enabled
        $today = now()->startOfDay();
        $endOfDay = $today->copy()->endOfDay();

        return Subscription::with('user')
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('auto_renew', true)
            ->whereBetween('expires_at', [$today, $endOfDay])
            ->get();
    }

    /**
     * Execute auto-renewal for a subscription.
     * 
     * @param Subscription $subscription
     * @return void
     * @throws \Exception
     */
    protected function executeAutoRenewal(Subscription $subscription): void
    {
        DB::beginTransaction();

        try {
            $renewalPeriod = $subscription->renewal_period ?? 'annually';
            $newExpiryDate = $this->calculateNewExpiryDate($subscription->expires_at, $renewalPeriod);

            // Renew the subscription
            $subscription->renew($newExpiryDate);

            // Log the renewal action
            $this->logRenewalAction($subscription, 'automatic', $renewalPeriod);

            DB::commit();

            Log::info("Auto-renewal completed for subscription {$subscription->id}", [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'old_expiry' => $subscription->getOriginal('expires_at'),
                'new_expiry' => $newExpiryDate->toDateTimeString(),
                'renewal_period' => $renewalPeriod
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate new expiry date based on renewal period.
     * 
     * @param Carbon $currentExpiry
     * @param string $renewalPeriod
     * @return Carbon
     */
    protected function calculateNewExpiryDate(Carbon $currentExpiry, string $renewalPeriod): Carbon
    {
        return match ($renewalPeriod) {
            'monthly' => $currentExpiry->addMonth(),
            'quarterly' => $currentExpiry->addMonths(3),
            'annually' => $currentExpiry->addYear(),
            default => $currentExpiry->addYear()
        };
    }

    /**
     * Log renewal action for audit trail.
     * 
     * @param Subscription $subscription
     * @param string $method manual or automatic
     * @param string $period
     * @return void
     */
    protected function logRenewalAction(Subscription $subscription, string $method, string $period): void
    {
        $oldExpiry = $subscription->getOriginal('expires_at');
        $newExpiry = $subscription->expires_at;
        $durationDays = $oldExpiry ? $newExpiry->diffInDays($oldExpiry) : 0;

        // Create renewal history record
        \App\Models\SubscriptionRenewal::create([
            'subscription_id' => $subscription->id,
            'user_id' => $method === 'manual' ? auth()->id() : null,
            'method' => $method,
            'period' => $period,
            'old_expires_at' => $oldExpiry,
            'new_expires_at' => $newExpiry,
            'duration_days' => $durationDays,
            'notes' => $method === 'automatic' ? 'Automatically renewed by system' : null,
        ]);

        Log::info("Subscription renewal logged", [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'method' => $method,
            'period' => $period,
            'renewed_at' => now()->toDateTimeString(),
            'old_expiry' => $oldExpiry,
            'new_expiry' => $newExpiry->toDateTimeString(),
            'duration_days' => $durationDays
        ]);
    }

    /**
     * Handle auto-renewal failure by notifying relevant parties.
     * 
     * @param Subscription $subscription
     * @param string $failureReason
     * @return void
     */
    protected function handleRenewalFailure(Subscription $subscription, string $failureReason): void
    {
        // Notify the organization admin
        try {
            $subscription->user->notify(new \App\Notifications\AutoRenewalFailedNotification($subscription, $failureReason));
        } catch (\Exception $e) {
            Log::error("Failed to send renewal failure notification to user", [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'error' => $e->getMessage()
            ]);
        }

        // Notify superadmins
        try {
            $superadmins = User::where('role', 'superadmin')->get();
            Notification::send($superadmins, new \App\Notifications\SuperadminRenewalFailureNotification($subscription, $failureReason));
        } catch (\Exception $e) {
            Log::error("Failed to send renewal failure notification to superadmins", [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
        }

        Log::error("Auto-renewal failure handled for subscription {$subscription->id}", [
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'failure_reason' => $failureReason
        ]);
    }

    /**
     * Get renewal history for a subscription or all subscriptions.
     * 
     * @param Subscription|null $subscription
     * @param array $filters
     * @return array
     */
    public function getRenewalHistory(?Subscription $subscription = null, array $filters = []): array
    {
        $query = \App\Models\SubscriptionRenewal::with(['subscription.user', 'user'])
            ->orderBy('created_at', 'desc');

        if ($subscription) {
            $query->where('subscription_id', $subscription->id);
        }

        // Apply filters
        if (!empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        if (!empty($filters['period'])) {
            $query->where('period', $filters['period']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['organization_id'])) {
            $query->whereHas('subscription', function ($q) use ($filters) {
                $q->where('user_id', $filters['organization_id']);
            });
        }

        $renewals = $query->paginate(25);

        return [
            'renewals' => $renewals->items(),
            'total_count' => $renewals->total(),
            'current_page' => $renewals->currentPage(),
            'per_page' => $renewals->perPage(),
            'last_page' => $renewals->lastPage(),
            'filters_applied' => $filters
        ];
    }
}