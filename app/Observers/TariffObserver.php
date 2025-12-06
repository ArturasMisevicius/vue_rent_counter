<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Tariff;
use Illuminate\Support\Facades\Log;

/**
 * TariffObserver
 * 
 * Observes tariff model events for audit logging and security monitoring.
 * 
 * Security Features:
 * - Logs all tariff creation, update, and deletion events
 * - Tracks manual vs provider-linked tariff creation
 * - Monitors tariff mode changes (manual ↔ provider)
 * - Records user context (ID, role, IP, user agent)
 * - Enables compliance and security auditing
 * 
 * Audit Log Channel:
 * Logs are written to the 'audit' channel configured in config/logging.php
 * with 365-day retention for compliance requirements.
 * 
 * @package App\Observers
 * @see config/logging.php
 * @see config/security.php
 */
class TariffObserver
{
    /**
     * Handle the Tariff "created" event.
     * 
     * Logs tariff creation with full context including:
     * - Tariff details (ID, name, manual mode status)
     * - Provider and remote_id information
     * - User context (ID, role, IP address, user agent)
     * 
     * Security: Enables tracking of manual tariff creation patterns
     * for abuse detection and compliance auditing.
     * 
     * Note: PII (IP address, user agent) is logged for security monitoring.
     * Ensure RedactSensitiveData processor is active in config/logging.php.
     *
     * @param Tariff $tariff The created tariff
     * @return void
     */
    public function created(Tariff $tariff): void
    {
        Log::channel('audit')->info('Tariff created', [
            'event' => 'tariff.created',
            'tariff_id' => $tariff->id,
            'name' => $this->sanitizeForLog($tariff->name),
            'is_manual' => $tariff->isManual(),
            'provider_id' => $tariff->provider_id,
            'remote_id' => $this->sanitizeForLog($tariff->remote_id),
            'configuration_type' => $tariff->configuration['type'] ?? null,
            'active_from' => $tariff->active_from?->toDateString(),
            'active_until' => $tariff->active_until?->toDateString(),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role?->value,
            'ip_address' => request()->ip(),
            'user_agent' => $this->sanitizeUserAgent(request()->userAgent()),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle the Tariff "updated" event.
     * 
     * Logs tariff updates with change tracking. Special attention to:
     * - Mode changes (manual ↔ provider-linked)
     * - Provider assignment changes
     * - Configuration changes
     * 
     * Security: Detects unauthorized modifications and tracks
     * conversion of manual tariffs to provider-linked tariffs.
     *
     * @param Tariff $tariff The updated tariff
     * @return void
     */
    public function updated(Tariff $tariff): void
    {
        $changes = $tariff->getChanges();
        
        // Log mode change with elevated severity
        if (isset($changes['provider_id'])) {
            $wasManual = is_null($tariff->getOriginal('provider_id'));
            $isManual = is_null($tariff->provider_id);
            
            Log::channel('audit')->warning('Tariff mode changed', [
                'event' => 'tariff.mode_changed',
                'tariff_id' => $tariff->id,
                'name' => $tariff->name,
                'was_manual' => $wasManual,
                'is_manual' => $isManual,
                'old_provider_id' => $tariff->getOriginal('provider_id'),
                'new_provider_id' => $tariff->provider_id,
                'remote_id' => $tariff->remote_id,
                'user_id' => auth()->id(),
                'user_role' => auth()->user()?->role?->value,
                'ip_address' => request()->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // Log general update
        Log::channel('audit')->info('Tariff updated', [
            'event' => 'tariff.updated',
            'tariff_id' => $tariff->id,
            'name' => $tariff->name,
            'is_manual' => $tariff->isManual(),
            'changed_fields' => array_keys($changes),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role?->value,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle the Tariff "deleted" event.
     * 
     * Logs tariff deletion with full context for audit trail.
     * 
     * Security: Enables tracking of tariff deletion patterns
     * and provides evidence for compliance audits.
     *
     * @param Tariff $tariff The deleted tariff
     * @return void
     */
    public function deleted(Tariff $tariff): void
    {
        Log::channel('audit')->warning('Tariff deleted', [
            'event' => 'tariff.deleted',
            'tariff_id' => $tariff->id,
            'name' => $tariff->name,
            'was_manual' => $tariff->isManual(),
            'provider_id' => $tariff->provider_id,
            'remote_id' => $tariff->remote_id,
            'configuration_type' => $tariff->configuration['type'] ?? null,
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role?->value,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle the Tariff "restored" event.
     * 
     * Logs tariff restoration from soft-delete.
     *
     * @param Tariff $tariff The restored tariff
     * @return void
     */
    public function restored(Tariff $tariff): void
    {
        Log::channel('audit')->info('Tariff restored', [
            'event' => 'tariff.restored',
            'tariff_id' => $tariff->id,
            'name' => $tariff->name,
            'is_manual' => $tariff->isManual(),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role?->value,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle the Tariff "force deleted" event.
     * 
     * Logs permanent tariff deletion (SUPERADMIN only).
     *
     * @param Tariff $tariff The force deleted tariff
     * @return void
     */
    public function forceDeleted(Tariff $tariff): void
    {
        Log::channel('audit')->critical('Tariff permanently deleted', [
            'event' => 'tariff.force_deleted',
            'tariff_id' => $tariff->id,
            'name' => $this->sanitizeForLog($tariff->name),
            'was_manual' => $tariff->isManual(),
            'user_id' => auth()->id(),
            'user_role' => auth()->user()?->role?->value,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Sanitize string for log output to prevent log injection.
     * 
     * Security: Removes newlines, carriage returns, and control characters
     * that could break log parsing or enable log injection attacks.
     * 
     * @param string|null $input The string to sanitize
     * @return string|null Sanitized string
     */
    protected function sanitizeForLog(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove newlines, carriage returns, and control characters
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/', '', $input);
        
        // Limit length to prevent log bloat
        return mb_substr($sanitized, 0, 255);
    }

    /**
     * Sanitize user agent for log output.
     * 
     * Security: Truncates and removes control characters from user agent strings.
     * 
     * @param string|null $userAgent The user agent string
     * @return string|null Sanitized user agent
     */
    protected function sanitizeUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null) {
            return null;
        }

        // Remove control characters
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/', '', $userAgent);
        
        // Limit length (user agents can be very long)
        return mb_substr($sanitized, 0, 500);
    }
}
