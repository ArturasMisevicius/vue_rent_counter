<?php

namespace App\Services;

use App\Models\OrganizationActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationService
{
    /**
     * Start impersonating a user.
     * 
     * Requirements: 11.1, 11.2, 11.3, 11.4
     */
    public function startImpersonation(User $targetUser, ?string $reason = null): void
    {
        $superadmin = Auth::user();

        if (!$superadmin || !$superadmin->isSuperadmin()) {
            throw new \RuntimeException('Only superadmins can impersonate users');
        }

        if ($superadmin->id === $targetUser->id) {
            throw new \RuntimeException('Cannot impersonate yourself');
        }

        // Store impersonation data in session
        Session::put('impersonation', [
            'superadmin_id' => $superadmin->id,
            'target_user_id' => $targetUser->id,
            'started_at' => now()->toIso8601String(),
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Log the impersonation start
        OrganizationActivityLog::create([
            'organization_id' => $targetUser->tenant_id,
            'user_id' => $superadmin->id,
            'action' => 'impersonation_started',
            'resource_type' => 'User',
            'resource_id' => $targetUser->id,
            'metadata' => [
                'target_user_name' => $targetUser->name,
                'target_user_email' => $targetUser->email,
                'target_user_role' => $targetUser->role->value,
                'reason' => $reason,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Switch to target user
        Auth::login($targetUser);
    }

    /**
     * End impersonation and restore superadmin session.
     * 
     * Requirements: 11.4
     */
    public function endImpersonation(): void
    {
        $impersonationData = Session::get('impersonation');

        if (!$impersonationData) {
            throw new \RuntimeException('No active impersonation session');
        }

        $currentUser = Auth::user();
        $superadminId = $impersonationData['superadmin_id'];
        $targetUser = User::find($impersonationData['target_user_id']);
        $startedAt = \Carbon\Carbon::parse($impersonationData['started_at']);
        $duration = now()->diffInSeconds($startedAt, true);

        // Log the impersonation end
        $logTarget = $targetUser ?? $currentUser;

        if ($logTarget && $logTarget->tenant_id !== null) {
            OrganizationActivityLog::create([
                'organization_id' => $logTarget->tenant_id,
                'user_id' => $superadminId,
                'action' => 'impersonation_ended',
                'resource_type' => 'User',
                'resource_id' => $logTarget->id,
                'metadata' => [
                    'target_user_name' => $logTarget->name,
                    'target_user_email' => $logTarget->email,
                    'duration_seconds' => $duration,
                    'started_at' => $impersonationData['started_at'],
                    'ended_at' => now()->toIso8601String(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        // Clear impersonation data
        Session::forget('impersonation');

        // Restore superadmin session
        $superadmin = User::find($superadminId);
        if ($superadmin) {
            Auth::login($superadmin);
        }
    }

    /**
     * Check if currently impersonating.
     */
    public function isImpersonating(): bool
    {
        return Session::has('impersonation');
    }

    /**
     * Get impersonation data.
     */
    public function getImpersonationData(): ?array
    {
        return Session::get('impersonation');
    }

    /**
     * Check if impersonation has timed out (30 minutes).
     * 
     * Requirements: 11.4
     */
    public function hasTimedOut(): bool
    {
        $impersonationData = Session::get('impersonation');

        if (!$impersonationData) {
            return false;
        }

        $startedAt = \Carbon\Carbon::parse($impersonationData['started_at']);
        $timeoutMinutes = 30;

        return now()->diffInMinutes($startedAt, true) >= $timeoutMinutes;
    }

    /**
     * Get the superadmin who is impersonating.
     */
    public function getSuperadmin(): ?User
    {
        $impersonationData = Session::get('impersonation');

        if (!$impersonationData) {
            return null;
        }

        return User::find($impersonationData['superadmin_id']);
    }

    /**
     * Get the target user being impersonated.
     */
    public function getTargetUser(): ?User
    {
        $impersonationData = Session::get('impersonation');

        if (!$impersonationData) {
            return null;
        }

        return User::find($impersonationData['target_user_id']);
    }
}
