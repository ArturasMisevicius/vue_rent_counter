<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;

/**
 * Superadmin Organization Observer
 * 
 * Handles audit logging for organization operations performed by superadmins.
 * This observer specifically tracks superadmin actions for security and
 * compliance purposes as part of the superadmin dashboard enhancement.
 * 
 * Requirements: 16.1, 16.2
 */
class SuperadminOrganizationObserver
{
    /**
     * Handle the Organization "creating" event.
     */
    public function creating(Organization $organization): void
    {
        $this->logSuperadminAction('creating', $organization, null, $organization->toArray());
    }

    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        $this->logSuperadminAction('created', $organization, null, $organization->toArray());
    }

    /**
     * Handle the Organization "updating" event.
     */
    public function updating(Organization $organization): void
    {
        $this->logSuperadminAction('updating', $organization, $organization->getOriginal(), $organization->getDirty());
    }

    /**
     * Handle the Organization "updated" event.
     */
    public function updated(Organization $organization): void
    {
        $changes = $organization->getChanges();
        
        if (!empty($changes)) {
            $this->logSuperadminAction('updated', $organization, $organization->getOriginal(), $changes);
        }
    }

    /**
     * Handle the Organization "deleting" event.
     */
    public function deleting(Organization $organization): void
    {
        $this->logSuperadminAction('deleting', $organization, $organization->toArray(), null);
    }

    /**
     * Handle the Organization "deleted" event.
     */
    public function deleted(Organization $organization): void
    {
        $this->logSuperadminAction('deleted', $organization, $organization->toArray(), null);
    }

    /**
     * Handle the Organization "restoring" event.
     */
    public function restoring(Organization $organization): void
    {
        $this->logSuperadminAction('restoring', $organization, null, $organization->toArray());
    }

    /**
     * Handle the Organization "restored" event.
     */
    public function restored(Organization $organization): void
    {
        $this->logSuperadminAction('restored', $organization, null, $organization->toArray());
    }

    /**
     * Handle the Organization "force deleting" event.
     */
    public function forceDeleting(Organization $organization): void
    {
        $this->logSuperadminAction('force_deleting', $organization, $organization->toArray(), null);
    }

    /**
     * Handle the Organization "force deleted" event.
     */
    public function forceDeleted(Organization $organization): void
    {
        $this->logSuperadminAction('force_deleted', $organization, $organization->toArray(), null);
    }

    /**
     * Log superadmin actions on organizations for audit compliance.
     *
     * @param string $action The action being performed
     * @param Organization $organization The organization being acted upon
     * @param array|null $beforeData The data before the change
     * @param array|null $afterData The data after the change
     * @return void
     */
    private function logSuperadminAction(string $action, Organization $organization, ?array $beforeData, ?array $afterData): void
    {
        $user = auth()->user();
        
        // Only log if the action is performed by a superadmin
        if (!$user || !$user->isSuperadmin()) {
            return;
        }

        $request = request();

        $auditLogger = Log::channel('audit');

        if (is_object($auditLogger) && method_exists($auditLogger, 'info')) {
            $auditLogger->info("Superadmin organization {$action}", [
                'action' => $action,
                'resource_type' => 'organization',
                'resource_id' => $organization->id,
                'resource_name' => $organization->name,
                'resource_slug' => $organization->slug,
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

            return;
        }

        Log::info("Superadmin organization {$action}", [
            'action' => $action,
            'resource_type' => 'organization',
            'resource_id' => $organization->id,
            'resource_name' => $organization->name,
            'resource_slug' => $organization->slug,
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
