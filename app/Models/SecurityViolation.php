<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SecuritySeverity;
use App\Enums\ThreatClassification;
use App\Scopes\TenantScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Security Violation Model
 * 
 * Represents security violations detected by the security headers
 * system, including CSP violations, XSS attempts, and other threats.
 */
final class SecurityViolation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'violation_type',
        'policy_directive',
        'blocked_uri',
        'document_uri',
        'referrer',
        'user_agent',
        'source_file',
        'line_number',
        'column_number',
        'severity_level',
        'threat_classification',
        'resolved_at',
        'resolution_notes',
        'metadata',
    ];

    protected $hidden = [
        'metadata', // Hide sensitive metadata from JSON serialization
        'user_agent', // Hide user agent for privacy
        'referrer', // Hide referrer for privacy
    ];

    protected $casts = [
        'metadata' => 'encrypted:array', // Encrypt sensitive metadata
        'resolved_at' => 'datetime',
        'severity_level' => SecuritySeverity::class,
        'threat_classification' => ThreatClassification::class,
        'line_number' => 'integer',
        'column_number' => 'integer',
        'blocked_uri' => 'encrypted', // Encrypt potentially sensitive URIs
        'document_uri' => 'encrypted',
        'referrer' => 'encrypted',
        'user_agent' => 'encrypted',
        'source_file' => 'encrypted',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    /**
     * Get the tenant that owns this violation.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if the violation is resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Check if the violation requires immediate attention.
     */
    public function requiresImmediateAttention(): bool
    {
        return $this->severity_level->requiresImmediateAttention() ||
               $this->threat_classification->requiresAutomatedResponse();
    }

    /**
     * Get the violation source location.
     */
    public function getSourceLocationAttribute(): ?string
    {
        if (!$this->source_file) {
            return null;
        }

        $location = $this->source_file;
        
        if ($this->line_number) {
            $location .= ":{$this->line_number}";
            
            if ($this->column_number) {
                $location .= ":{$this->column_number}";
            }
        }

        return $location;
    }

    /**
     * Scope to filter by violation type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('violation_type', $type);
    }

    /**
     * Scope to filter by severity level.
     */
    public function scopeWithSeverity($query, SecuritySeverity $severity)
    {
        return $query->where('severity_level', $severity);
    }

    /**
     * Scope to filter unresolved violations.
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope to filter recent violations.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}