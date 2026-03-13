<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Super Admin Audit Log Model
 * 
 * Comprehensive audit logging for all super admin actions across the system.
 * Provides detailed tracking of who did what, when, and to which resources.
 */
class SuperAdminAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'tenant_id',
        'changes',
        'ip_address',
        'user_agent',
        'impersonation_session_id',
        'description',
        'metadata',
        'severity',
    ];

    protected $casts = [
        'changes' => 'array',
        'action' => AuditAction::class,
        'metadata' => 'array',
    ];

    /**
     * Get the admin user who performed the action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the tenant associated with this action (if applicable).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }

    /**
     * Get the target model that was affected.
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log a super admin action.
     */
    public static function logAction(
        AuditAction $action,
        ?Model $target = null,
        ?array $changes = null,
        ?string $description = null,
        ?array $metadata = null,
        ?int $adminId = null,
        ?int $tenantId = null,
        ?string $impersonationSessionId = null
    ): self {
        return static::create([
            'admin_id' => $adminId ?? auth()->id(),
            'action' => $action,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'tenant_id' => $tenantId,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'impersonation_session_id' => $impersonationSessionId,
            'description' => $description,
            'metadata' => $metadata,
            'severity' => $action->getSeverity(),
        ]);
    }

    /**
     * Get a human-readable description of the action.
     */
    public function getFormattedDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $adminName = $this->admin?->name ?? 'Unknown Admin';
        $targetName = $this->getTargetName();
        
        return match($this->action) {
            AuditAction::TENANT_CREATED => "{$adminName} created tenant {$targetName}",
            AuditAction::TENANT_UPDATED => "{$adminName} updated tenant {$targetName}",
            AuditAction::TENANT_SUSPENDED => "{$adminName} suspended tenant {$targetName}",
            AuditAction::TENANT_ACTIVATED => "{$adminName} activated tenant {$targetName}",
            AuditAction::TENANT_DELETED => "{$adminName} deleted tenant {$targetName}",
            AuditAction::USER_IMPERSONATED => "{$adminName} impersonated user {$targetName}",
            AuditAction::IMPERSONATION_ENDED => "{$adminName} ended impersonation session",
            AuditAction::BULK_OPERATION => "{$adminName} performed bulk operation",
            AuditAction::SYSTEM_CONFIG_CHANGED => "{$adminName} changed system configuration",
            default => "{$adminName} performed {$this->action->getLabel()}",
        };
    }

    /**
     * Get the target name for display.
     */
    private function getTargetName(): string
    {
        if (!$this->target) {
            return $this->target_type ? "#{$this->target_id}" : 'Unknown';
        }

        return match(true) {
            $this->target instanceof Organization => $this->target->name,
            $this->target instanceof User => $this->target->name,
            method_exists($this->target, 'getName') => $this->target->getName(),
            isset($this->target->name) => $this->target->name,
            isset($this->target->title) => $this->target->title,
            default => "#{$this->target->getKey()}",
        };
    }

    /**
     * Get changes summary for display.
     */
    public function getChangesSummary(): ?string
    {
        if (empty($this->changes)) {
            return null;
        }

        $summary = [];
        foreach ($this->changes as $field => $change) {
            if (is_array($change) && isset($change['old'], $change['new'])) {
                $summary[] = "{$field}: {$change['old']} → {$change['new']}";
            } else {
                $summary[] = "{$field}: " . json_encode($change);
            }
        }

        return implode(', ', $summary);
    }

    /**
     * Scope to actions by a specific admin.
     */
    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope to actions on a specific tenant.
     */
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to actions of a specific type.
     */
    public function scopeByAction($query, AuditAction $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to actions within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to high-severity actions.
     */
    public function scopeHighSeverity($query)
    {
        return $query->where('severity', 'high');
    }

    /**
     * Scope to impersonation actions.
     */
    public function scopeImpersonation($query)
    {
        return $query->whereNotNull('impersonation_session_id');
    }
}