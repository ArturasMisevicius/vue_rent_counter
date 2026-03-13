<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;

/**
 * Job for processing bulk operations in the background
 * 
 * Handles bulk suspend, reactivate, plan changes, and other
 * operations that may take time with large datasets
 */
class BulkOperationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $operation,
        public array $recordIds,
        public array $parameters = [],
        public ?int $userId = null
    ) {
        $this->onQueue('bulk-operations');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting bulk operation', [
            'operation' => $this->operation,
            'record_count' => count($this->recordIds),
            'user_id' => $this->userId,
        ]);

        DB::beginTransaction();

        try {
            $results = match ($this->operation) {
                'suspend_organizations' => $this->suspendOrganizations(),
                'reactivate_organizations' => $this->reactivateOrganizations(),
                'change_organization_plan' => $this->changeOrganizationPlan(),
                'renew_subscriptions' => $this->renewSubscriptions(),
                'suspend_subscriptions' => $this->suspendSubscriptions(),
                'activate_subscriptions' => $this->activateSubscriptions(),
                'deactivate_users' => $this->deactivateUsers(),
                'reactivate_users' => $this->reactivateUsers(),
                default => throw new \InvalidArgumentException("Unknown operation: {$this->operation}")
            };

            DB::commit();

            Log::info('Bulk operation completed successfully', [
                'operation' => $this->operation,
                'results' => $results,
            ]);

            // Invalidate relevant caches
            $this->invalidateCaches();

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk operation failed', [
                'operation' => $this->operation,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Suspend multiple organizations
     */
    private function suspendOrganizations(): array
    {
        $reason = $this->parameters['reason'] ?? 'Bulk suspension';
        $successCount = 0;
        $errors = [];

        foreach ($this->recordIds as $id) {
            try {
                $organization = Organization::findOrFail($id);
                $organization->suspend($reason);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Organization {$id}: " . $e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Reactivate multiple organizations
     */
    private function reactivateOrganizations(): array
    {
        $successCount = 0;
        $errors = [];

        foreach ($this->recordIds as $id) {
            try {
                $organization = Organization::findOrFail($id);
                $organization->reactivate();
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Organization {$id}: " . $e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Change plan for multiple organizations
     */
    private function changeOrganizationPlan(): array
    {
        $newPlan = $this->parameters['plan'];
        $successCount = 0;
        $errors = [];

        foreach ($this->recordIds as $id) {
            try {
                $organization = Organization::findOrFail($id);
                $organization->upgradePlan($newPlan);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Organization {$id}: " . $e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Renew multiple subscriptions
     */
    private function renewSubscriptions(): array
    {
        $duration = $this->parameters['duration'] ?? 365; // days
        $successCount = 0;
        $errors = [];

        foreach ($this->recordIds as $id) {
            try {
                $subscription = Subscription::findOrFail($id);
                $newExpiryDate = $subscription->expires_at->addDays($duration);
                $subscription->renew($newExpiryDate);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Subscription {$id}: " . $e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Suspend multiple subscriptions
     */
    private function suspendSubscriptions(): array
    {
        $successCount = 0;
        $errors = [];

        foreach ($this->recordIds as $id) {
            try {
                $subscription = Subscription::findOrFail($id);
                $subscription->suspend();
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Subscription {$id}: " . $e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Activate multiple subscriptions
     */
    private function activateSubscriptions(): array
    {
        $successCount = 0;
        $errors = [];

        foreach ($this->recordIds as $id) {
            try {
                $subscription = Subscription::findOrFail($id);
                $subscription->activate();
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "Subscription {$id}: " . $e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Deactivate multiple users
     */
    private function deactivateUsers(): array
    {
        $successCount = 0;
        $errors = [];

        foreach ($this->recordIds as $id) {
            try {
                $user = User::findOrFail($id);
                $user->update(['is_active' => false]);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "User {$id}: " . $e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Reactivate multiple users
     */
    private function reactivateUsers(): array
    {
        $successCount = 0;
        $errors = [];

        foreach ($this->recordIds as $id) {
            try {
                $user = User::findOrFail($id);
                $user->update(['is_active' => true]);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = "User {$id}: " . $e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Invalidate relevant caches after bulk operations
     */
    private function invalidateCaches(): void
    {
        $cacheService = app(\App\Services\DashboardCacheService::class);
        
        if (str_contains($this->operation, 'organization')) {
            $cacheService->invalidateOrganizationCaches();
        }
        
        if (str_contains($this->operation, 'subscription')) {
            $cacheService->invalidateSubscriptionCaches();
        }
        
        // Always invalidate activity stats since bulk operations create activity
        \Illuminate\Support\Facades\Cache::forget('superadmin.dashboard.activity_stats');
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk operation job failed', [
            'operation' => $this->operation,
            'record_count' => count($this->recordIds),
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}