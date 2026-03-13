<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

/**
 * Superadmin Subscription Observer
 * 
 * Handles audit logging for subscription operations performed by superadmins.
 * This observer specifically tracks superadmin actions for security and
 * compliance purposes as part of the superadmin dashboard enhancement.
 * 
 * Requirements: 16.1, 16.2
 */
class SuperadminSubscriptionObserver
{
    /**
     * Handle the Subscription "creating" event.
     */
    public function creating(Subscription $subscription): void
    {
        $this->logSuperadminAction('creating', $subscription, null, $subscription->toArray());
    }

    /**
     * Handle the Subscription "created" event.
     */
    public function created(Subscription $subscription): void
    {
        $this->logSuperadminAction('created', $subscription, null, $subscription->toArray());
    }

    /**
     * Handle the Subscription "updating" event.
     */
    public function updating(Subscription $subscription): void
    {
        $this->logSuperadminAction('updating', $subscription, $subscription->getOriginal(), $subscription->getDirty());
    }

    /**
     * Handle the Subscription "updated" event.
     */
    public function updated(Subscription $subscription): void
    {
        $changes = $subscription->getChanges();
        
        if (!empty($changes)) {
            $this->logSuperadminAction('updated', $subscription, $subscription->getOriginal(), $changes);
        }
    }

    /**
     * Handle the Subscription "deleting" event.
     */
    public function deleting(Subscription $subscription): void
    {
        $this->logSuperadminAction('deleting', $subscription, $subscription->toArray(), null);
    }

    /**
     * Handle the Subscription "deleted" event.
     */
    public function deleted(Subscription $subscription): void
    {
        $this->logSuperadminAction('deleted', $subscription, $subscription->toArray(), null);
    }

    /**
     * Handle the Subscription "restoring" event.
     */
    public function restoring(Subscription $subscription): void
    {
        $this->logSuperadminAction('restoring', $subscription, null, $subscription->toArray());
    }

    /**
     * Handle the Subscription "restored" event.
     */
    public function restored(Subscription $subscription): void
    {
        $this->logSuperadminAction('restored', $subscription, null, $subscription->toArray());
    }

    /**
     * Handle the Subscription "force deleting" event.
     */
    public function forceDeleting(Subscription $subscription): void
    {
        $this->logSuperadminAction('force_deleting', $subscription, $subscription->toArray(), null);
    }

    /**
     * Handle the Subscription "force deleted" event.
     */
    public function forceDeleted(Subscription $subscription): void
    {
        $this->logSuperadminAction('force_deleted', $subscription, $subscription->toArray(), null);
    }

    /**
     * Log superadmin actions on subscriptions for audit compliance.
     *
     * @param string $action The action being performed
     * @param Subscription $subscription The subscription being acted upon
     * @param array|null $beforeData The data before the change
     * @param array|null $afterData The data after the change
     * @return void
     */
    private function logSuperadminAction(string $action, Subscription $subscription, ?array $beforeData, ?array $afterData): void
    {
        $user = auth()->user();
        
        // Only log if the action is performed by a superadmin
        if (!$user || !$user->isSuperadmin()) {
            return;
        }

        $request = request();
        
        Log::channel('audit')->info("Superadmin subscription {$action}", [
            'action' => $action,
            'resource_type' => 'subscription',
            'resource_id' => $subscription->id,
            'subscription_plan' => $subscription->plan_type,
            'subscription_status' => $subscription->status,
            'subscription_user_id' => $subscription->user_id,
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'actor_role' => $user->role->value,
            'before_data' => $beforeData,
            'after_data' => $afterData,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'session_id' => session()->getId(),
        ]);
    }
}