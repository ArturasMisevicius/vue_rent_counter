<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * AuditLog
 * 
 * Polymorphic audit trail for tracking changes to any model.
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int|null $user_id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $event
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $notes
 * @property-read Model $auditable
 * @property-read User|null $user
 */
class AuditLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'old_values' => 'encrypted:array',  // SECURITY: Encrypted at rest
        'new_values' => 'encrypted:array',  // SECURITY: Encrypted at rest
    ];

    protected $hidden = [
        'ip_address',  // SECURITY: Don't expose in JSON responses
        'user_agent',  // SECURITY: Don't expose in JSON responses
    ];

    /**
     * Get the auditable model (polymorphic).
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter by model type.
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('auditable_type', $modelType);
    }

    /**
     * Get changes as a formatted array with PII redaction.
     */
    public function getChanges(): array
    {
        $changes = [];
        
        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $this->redactPII($key, $oldValue),
                        'new' => $this->redactPII($key, $newValue),
                    ];
                }
            }
        }
        
        return $changes;
    }

    /**
     * Redact PII from audit values.
     * 
     * @param string $key Field name
     * @param mixed $value Field value
     * @return mixed Redacted value
     */
    private function redactPII(string $key, mixed $value): mixed
    {
        $piiFields = [
            'password',
            'password_confirmation',
            'remember_token',
            'email',
            'phone',
            'ssn',
            'credit_card',
            'bank_account',
            'api_key',
            'api_secret',
            'token',
        ];
        
        if (in_array(strtolower($key), $piiFields)) {
            return '[REDACTED]';
        }
        
        // Redact email-like values
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return '[REDACTED_EMAIL]';
        }
        
        return $value;
    }

    /**
     * Scope to exclude old audit logs (retention policy).
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days Number of days to retain (default: 90)
     */
    public function scopeWithinRetention($query, int $days = 90)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
