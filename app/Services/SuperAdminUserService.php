<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SuperAdminUserInterface;
use App\Data\User\ActivityReport;
use App\Data\User\BulkOperationResult;
use App\Data\User\ImpersonationSession;
use App\Enums\AuditAction;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

final readonly class SuperAdminUserService implements SuperAdminUserInterface
{
    public function impersonateUser(User $user, int $adminId): ImpersonationSession
    {
        $adminUser = User::find($adminId);
        
        if (!$adminUser) {
            Log::error('Impersonation failed: Admin user not found', [
                'admin_id' => $adminId,
                'target_user_id' => $user->id,
            ]);
            throw new \InvalidArgumentException('Admin user not found');
        }
        
        if (!$this->canImpersonate($adminUser, $user)) {
            Log::warning('Unauthorized impersonation attempt', [
                'admin_id' => $adminId,
                'target_user_id' => $user->id,
                'admin_roles' => $adminUser->roles->pluck('name'),
                'target_roles' => $user->roles->pluck('name'),
            ]);
            throw new \InvalidArgumentException('Unauthorized impersonation attempt');
        }

        // Create impersonation session
        $sessionId = uniqid('imp_', true);
        
        // Store impersonation data in session
        Session::put('impersonation', [
            'session_id' => $sessionId,
            'admin_id' => $adminUser->id,
            'target_user_id' => $user->id,
            'started_at' => now()->toISOString(),
        ]);

        try {
            // Log the impersonation start
            SuperAdminAuditLog::create([
                'admin_id' => $adminUser->id,
                'action' => AuditAction::USER_IMPERSONATED,
                'target_type' => User::class,
                'target_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'changes' => [
                    'action' => 'started',
                    'target_user_email' => $user->email,
                    'session_id' => $sessionId,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'impersonation_session_id' => $sessionId,
            ]);

            // Switch to target user
            Auth::login($user);
        } catch (\Exception $e) {
            Log::error('Failed to start impersonation session', [
                'admin_id' => $adminUser->id,
                'target_user_id' => $user->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to start impersonation session: ' . $e->getMessage(), 0, $e);
        }

        Log::info('User impersonation started', [
            'admin_id' => $adminUser->id,
            'target_user_id' => $user->id,
            'session_id' => $sessionId,
        ]);

        return new ImpersonationSession(
            sessionId: $sessionId,
            adminId: $adminUser->id,
            targetUserId: $user->id,
            startedAt: now(),
            isActive: true,
        );
    }

    public function endImpersonation(int $sessionId): void
    {
        $impersonationData = Session::get('impersonation');
        
        if (!$impersonationData) {
            Log::warning('Attempted to end impersonation with no active session', [
                'session_id' => $sessionId,
                'current_user_id' => Auth::id(),
            ]);
            throw new \RuntimeException('No active impersonation session found');
        }

        if ($impersonationData['session_id'] !== $sessionId) {
            Log::warning('Session ID mismatch when ending impersonation', [
                'expected_session_id' => $sessionId,
                'actual_session_id' => $impersonationData['session_id'],
            ]);
            throw new \RuntimeException('Session ID mismatch');
        }

        $sessionId = $impersonationData['session_id'];
        $adminId = $impersonationData['admin_id'];
        $targetUserId = $impersonationData['target_user_id'];

        try {
            // Get admin user to switch back
            $adminUser = User::find($adminId);
            if (!$adminUser) {
                Log::error('Admin user not found when ending impersonation', [
                    'admin_id' => $adminId,
                    'session_id' => $sessionId,
                ]);
                throw new \RuntimeException('Admin user not found');
            }

            // Log the impersonation end
            SuperAdminAuditLog::create([
                'admin_id' => $adminId,
                'action' => AuditAction::IMPERSONATION_ENDED,
                'target_type' => User::class,
                'target_id' => $targetUserId,
                'tenant_id' => User::find($targetUserId)?->tenant_id,
                'changes' => [
                    'action' => 'ended',
                    'duration_minutes' => now()->diffInMinutes($impersonationData['started_at']),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'impersonation_session_id' => $sessionId,
            ]);

            // Clear impersonation session
            Session::forget('impersonation');

            // Switch back to admin user
            Auth::login($adminUser);
        } catch (\Exception $e) {
            Log::error('Failed to end impersonation session', [
                'admin_id' => $adminId,
                'target_user_id' => $targetUserId,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to end impersonation session: ' . $e->getMessage(), 0, $e);
        }

        Log::info('User impersonation ended', [
            'admin_id' => $adminId,
            'target_user_id' => $targetUserId,
            'session_id' => $sessionId,
        ]);
    }

    public function bulkUpdateUsers(Collection $users, array $updates, int $adminId): BulkOperationResult
    {
        $startTime = microtime(true);
        $successful = 0;
        $failed = 0;
        $errors = [];
        $successfulIds = [];
        $failedIds = [];

        DB::beginTransaction();

        try {
            foreach ($users as $user) {
                try {
                    $originalData = $user->toArray();
                    
                    // Apply updates
                    $user->update($updates);
                    
                    // Log the update
                    SuperAdminAuditLog::create([
                        'admin_id' => $adminId,
                        'action' => AuditAction::BULK_OPERATION,
                        'target_type' => User::class,
                        'target_id' => $user->id,
                        'tenant_id' => $user->tenant_id,
                        'changes' => [
                            'operation' => 'bulk_update',
                            'updates' => $updates,
                            'original' => $originalData,
                        ],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);

                    $successful++;
                    $successfulIds[] = $user->id;
                } catch (\Exception $e) {
                    $failed++;
                    $failedIds[] = $user->id;
                    $errors[] = "User {$user->id}: {$e->getMessage()}";
                    
                    Log::error('Bulk user update failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'updates' => $updates,
                        'admin_id' => $adminId,
                    ]);
                }
            }

            DB::commit();

            $executionTime = (microtime(true) - $startTime) * 1000;

            Log::info('Bulk user update completed', [
                'admin_id' => $adminId,
                'total_users' => $users->count(),
                'successful' => $successful,
                'failed' => $failed,
                'execution_time_ms' => $executionTime,
            ]);

            return BulkOperationResult::mixed(
                total: $users->count(),
                successful: $successful,
                failed: $failed,
                errors: $errors,
                successfulIds: $successfulIds,
                failedIds: $failedIds,
                executionTime: $executionTime,
            );

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk user update transaction failed', [
                'admin_id' => $adminId,
                'total_users' => $users->count(),
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Bulk user update failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getUserActivityAcrossTenants(User $user): ActivityReport
    {
        // Get user's activity across all tenants they have access to
        $activities = collect();
        
        // Get login history
        $loginHistory = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->limit(50)
            ->get();

        // Get audit logs where user was the target or actor
        $auditLogs = SuperAdminAuditLog::where(function ($query) use ($user) {
            $query->where('admin_id', $user->id)
                  ->orWhere('target_id', $user->id);
        })
        ->orderBy('created_at', 'desc')
        ->limit(100)
        ->get();

        // Get user's organization activity
        $organizationActivity = $user->organization?->activityLogs()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get() ?? collect();

        return new ActivityReport(
            userId: $user->id,
            userName: $user->name,
            userEmail: $user->email,
            tenantId: $user->tenant_id,
            lastLoginAt: $user->last_login_at,
            totalSessions: $loginHistory->count(),
            recentSessions: $loginHistory->toArray(),
            auditLogEntries: $auditLogs->count(),
            recentAuditLogs: $auditLogs->toArray(),
            organizationActivity: $organizationActivity->toArray(),
            generatedAt: now(),
        );
    }

    public function suspendUserGlobally(User $user, string $reason, int $adminId): void
    {

        DB::beginTransaction();

        try {
            // Suspend the user
            $user->update([
                'is_active' => false,
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);

            // Invalidate all user sessions
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->delete();

            // Log the suspension
            SuperAdminAuditLog::create([
                'admin_id' => $adminId,
                'action' => AuditAction::USER_SUSPENDED,
                'target_type' => User::class,
                'target_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'changes' => [
                    'reason' => $reason,
                    'suspended_at' => now()->toISOString(),
                    'sessions_invalidated' => true,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            Log::info('User suspended globally', [
                'admin_id' => $adminId,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'reason' => $reason,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to suspend user globally', [
                'admin_id' => $adminId,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'reason' => $reason,
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to suspend user globally: ' . $e->getMessage(), 0, $e);
        }
    }

    public function reactivateUserGlobally(User $user, int $adminId): void
    {
        $user->update([
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        // Log the reactivation
        SuperAdminAuditLog::create([
            'admin_id' => $adminId,
            'action' => AuditAction::USER_REACTIVATED,
            'target_type' => User::class,
            'target_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'changes' => [
                'action' => 'reactivated',
                'reactivated_at' => now()->toISOString(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::info('User reactivated globally', [
            'admin_id' => $adminId,
            'user_id' => $user->id,
        ]);
    }

    public function getAllUsers(array $filters = []): Collection
    {
        $query = User::query()
            ->with(['organization', 'roles']);

        // Apply filters
        if (!empty($filters['status'])) {
            match ($filters['status']) {
                'active' => $query->where('is_active', true),
                'suspended' => $query->whereNotNull('suspended_at'),
                'inactive' => $query->where('is_active', false),
                default => null,
            };
        }

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        if (!empty($filters['created_before'])) {
            $query->where('created_at', '<=', $filters['created_before']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getUsersByTenant(int $tenantId): Collection
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->with(['roles'])
            ->orderBy('name')
            ->get();
    }

    public function getActiveImpersonationSessions(): Collection
    {
        // Get active sessions from database or cache
        // This is a simplified implementation
        return collect();
    }

    public function forceLogoutUser(User $user, int $adminId): void
    {
        // Invalidate all user sessions
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        // Log the forced logout
        SuperAdminAuditLog::create([
            'admin_id' => $adminId,
            'action' => AuditAction::USER_FORCE_LOGOUT,
            'target_type' => User::class,
            'target_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'changes' => [
                'action' => 'force_logout',
                'logged_out_at' => now()->toISOString(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::info('User forced logout', [
            'admin_id' => $adminId,
            'user_id' => $user->id,
        ]);
    }

    public function isImpersonating(): bool
    {
        return Session::has('impersonation');
    }

    public function getCurrentImpersonationSession(): ?ImpersonationSession
    {
        $impersonationData = Session::get('impersonation');
        
        if (!$impersonationData) {
            return null;
        }

        return new ImpersonationSession(
            sessionId: $impersonationData['session_id'],
            adminId: $impersonationData['admin_id'],
            targetUserId: $impersonationData['target_user_id'],
            startedAt: \Carbon\Carbon::parse($impersonationData['started_at']),
            isActive: true,
        );
    }

    private function canImpersonate(User $admin, User $target): bool
    {
        // Super admin can impersonate anyone except other super admins
        if ($admin->hasRole('super_admin')) {
            return !$target->hasRole('super_admin');
        }

        return false;
    }
}