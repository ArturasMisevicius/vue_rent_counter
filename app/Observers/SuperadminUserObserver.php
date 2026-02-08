<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Superadmin User Observer
 * 
 * Handles audit logging for user operations performed by superadmins.
 * This observer specifically tracks superadmin actions for security and
 * compliance purposes as part of the superadmin dashboard enhancement.
 * 
 * Requirements: 16.1, 16.2
 */
class SuperadminUserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        $this->logSuperadminAction('creating', $user, null, $user->toArray());
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->logSuperadminAction('created', $user, null, $user->toArray());
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(User $user): void
    {
        $this->logSuperadminAction('updating', $user, $user->getOriginal(), $user->getDirty());
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        
        if (!empty($changes)) {
            // Log critical changes with higher severity
            $criticalFields = ['role', 'is_active', 'tenant_id', 'password', 'email'];
            $hasCriticalChanges = !empty(array_intersect(array_keys($changes), $criticalFields));
            
            $this->logSuperadminAction('updated', $user, $user->getOriginal(), $changes, $hasCriticalChanges);
        }
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        $this->logSuperadminAction('deleting', $user, $user->toArray(), null, true);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->logSuperadminAction('deleted', $user, $user->toArray(), null, true);
    }

    /**
     * Handle the User "restoring" event.
     */
    public function restoring(User $user): void
    {
        $this->logSuperadminAction('restoring', $user, null, $user->toArray(), true);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        $this->logSuperadminAction('restored', $user, null, $user->toArray(), true);
    }

    /**
     * Handle the User "force deleting" event.
     */
    public function forceDeleting(User $user): void
    {
        $this->logSuperadminAction('force_deleting', $user, $user->toArray(), null, true);
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        $this->logSuperadminAction('force_deleted', $user, $user->toArray(), null, true);
    }

    /**
     * Log superadmin actions on users for audit compliance.
     *
     * @param string $action The action being performed
     * @param User $targetUser The user being acted upon
     * @param array|null $beforeData The data before the change
     * @param array|null $afterData The data after the change
     * @param bool $isCritical Whether this is a critical security operation
     * @return void
     */
    private function logSuperadminAction(string $action, User $targetUser, ?array $beforeData, ?array $afterData, bool $isCritical = false): void
    {
        $actor = auth()->user();
        
        // Only log if the action is performed by a superadmin
        if (!$actor || !$actor->isSuperadmin()) {
            return;
        }

        $request = request();
        
        // Sanitize sensitive data from logs
        $sanitizedBefore = $this->sanitizeUserData($beforeData);
        $sanitizedAfter = $this->sanitizeUserData($afterData);
        
        $logLevel = $isCritical ? 'warning' : 'info';
        $logMessage = "Superadmin user {$action}" . ($isCritical ? ' (CRITICAL)' : '');

        $auditLogger = Log::channel('audit');

        if (is_object($auditLogger) && method_exists($auditLogger, $logLevel)) {
            $auditLogger->{$logLevel}($logMessage, [
                'action' => $action,
                'resource_type' => 'user',
                'resource_id' => $targetUser->id,
                'target_user_email' => $targetUser->email,
                'target_user_role' => $targetUser->role->value,
                'target_user_tenant_id' => $targetUser->tenant_id,
                'target_user_organization' => $targetUser->organization_name,
                'actor_id' => $actor->id,
                'actor_email' => $actor->email,
                'actor_role' => $actor->role->value,
                'before_data' => $sanitizedBefore,
                'after_data' => $sanitizedAfter,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toIso8601String(),
                'session_id' => session()->getId(),
                'is_critical' => $isCritical,
            ]);

            return;
        }

        Log::{$logLevel}($logMessage, [
            'action' => $action,
            'resource_type' => 'user',
            'resource_id' => $targetUser->id,
            'target_user_email' => $targetUser->email,
            'target_user_role' => $targetUser->role->value,
            'target_user_tenant_id' => $targetUser->tenant_id,
            'target_user_organization' => $targetUser->organization_name,
            'actor_id' => $actor->id,
            'actor_email' => $actor->email,
            'actor_role' => $actor->role->value,
            'before_data' => $sanitizedBefore,
            'after_data' => $sanitizedAfter,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'session_id' => session()->getId(),
            'is_critical' => $isCritical,
        ]);
    }

    /**
     * Sanitize user data to remove sensitive information from logs.
     *
     * @param array|null $data
     * @return array|null
     */
    private function sanitizeUserData(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        $sensitiveFields = ['password', 'remember_token', 'email_verified_at'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}
