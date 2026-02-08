<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Assign Role Action
 * 
 * Single responsibility: Assign or change a user's role.
 * Includes audit logging for security compliance.
 * 
 * @package App\Actions
 */
final class AssignRoleAction
{
    /**
     * Execute the action to assign a role to a user.
     *
     * @param User $user The user to assign role to
     * @param UserRole $role The role to assign
     * @param User|null $performedBy The user performing the action
     * @return User The updated user
     */
    public function execute(User $user, UserRole $role, ?User $performedBy = null): User
    {
        $previousRole = $user->role;

        DB::transaction(function () use ($user, $role, $previousRole, $performedBy) {
            // Update user role
            $user->update(['role' => $role]);

            // Log the role change
            $this->logRoleChange($user, $previousRole, $role, $performedBy);
        });

        return $user->fresh();
    }

    /**
     * Log role change for audit trail.
     *
     * @param User $user
     * @param UserRole $previousRole
     * @param UserRole $newRole
     * @param User|null $performedBy
     */
    private function logRoleChange(
        User $user,
        UserRole $previousRole,
        UserRole $newRole,
        ?User $performedBy
    ): void {
        Log::info('User role changed', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'previous_role' => $previousRole->value,
            'new_role' => $newRole->value,
            'performed_by' => $performedBy?->id,
            'tenant_id' => $user->tenant_id,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Insert audit record
        DB::table('user_assignments_audit')->insert([
            'user_id' => $user->id,
            'performed_by' => $performedBy?->id ?? $user->id,
            'action' => 'role_changed',
            'reason' => "Role changed from {$previousRole->value} to {$newRole->value}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
