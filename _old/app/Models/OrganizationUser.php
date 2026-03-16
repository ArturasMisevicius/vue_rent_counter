<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrganizationUser Pivot Model
 * 
 * Manages the many-to-many relationship between Organizations and Users
 * with role-based permissions and membership tracking
 */
class OrganizationUser extends Pivot
{
    protected $table = 'organization_user';

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
        'permissions',
        'joined_at',
        'left_at',
        'is_active',
        'invited_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who invited this member
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Add permission
     */
    public function addPermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            $this->permissions = $permissions;
            $this->save();
        }
    }

    /**
     * Remove permission
     */
    public function removePermission(string $permission): void
    {
        $permissions = $this->permissions ?? [];
        $this->permissions = array_values(array_diff($permissions, [$permission]));
        $this->save();
    }

    /**
     * Set multiple permissions
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = array_unique($permissions);
        $this->save();
    }

    /**
     * Get membership duration in days
     */
    public function getMembershipDuration(): int
    {
        $endDate = $this->left_at ?? now();
        return $this->joined_at->diffInDays($endDate);
    }

    /**
     * Check if membership is active
     */
    public function isActiveMembership(): bool
    {
        return $this->is_active && $this->left_at === null;
    }

    /**
     * Deactivate membership
     */
    public function deactivate(?string $reason = null): void
    {
        $this->update([
            'is_active' => false,
            'left_at' => now(),
        ]);
    }

    /**
     * Reactivate membership
     */
    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'left_at' => null,
        ]);
    }

    /**
     * Get role display name
     */
    public function getRoleDisplayName(): string
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'viewer' => 'Viewer',
            default => ucfirst($this->role),
        };
    }
}