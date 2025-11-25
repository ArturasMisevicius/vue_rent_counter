<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Auditable Trait
 * 
 * Add this trait to any model that needs audit trail tracking.
 * Automatically creates audit logs on create, update, delete, and restore events.
 */
trait Auditable
{
    /**
     * Boot the auditable trait.
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->auditEvent('created', null, $model->getAuditableAttributes());
        });

        static::updated(function ($model) {
            if ($model->isDirty()) {
                $model->auditEvent(
                    'updated',
                    $model->getOriginal(),
                    $model->getAttributes()
                );
            }
        });

        static::deleted(function ($model) {
            $model->auditEvent('deleted', $model->getOriginal(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->auditEvent('restored', null, $model->getAttributes());
            });
        }
    }

    /**
     * Get all audit logs for this model.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Create an audit log entry.
     */
    protected function auditEvent(string $event, ?array $oldValues, ?array $newValues): void
    {
        // Filter out attributes that shouldn't be audited
        $oldValues = $oldValues ? $this->filterAuditableAttributes($oldValues) : null;
        $newValues = $newValues ? $this->filterAuditableAttributes($newValues) : null;

        AuditLog::create([
            'tenant_id' => $this->tenant_id ?? session('tenant_id'),
            'user_id' => auth()->id(),
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get attributes that should be audited.
     * Override this method in your model to customize.
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();
        return $this->filterAuditableAttributes($attributes);
    }

    /**
     * Filter out attributes that shouldn't be audited.
     */
    protected function filterAuditableAttributes(array $attributes): array
    {
        // Remove timestamps and sensitive data
        $excluded = array_merge(
            ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'],
            $this->getAuditExclude()
        );

        return array_diff_key($attributes, array_flip($excluded));
    }

    /**
     * Get additional attributes to exclude from audit.
     * Override this in your model.
     */
    protected function getAuditExclude(): array
    {
        return property_exists($this, 'auditExclude') ? $this->auditExclude : [];
    }

    /**
     * Get the latest audit log.
     */
    public function latestAudit(): ?AuditLog
    {
        return $this->auditLogs()->first();
    }

    /**
     * Get audit logs for a specific event.
     */
    public function auditLogsForEvent(string $event)
    {
        return $this->auditLogs()->where('event', $event);
    }
}
