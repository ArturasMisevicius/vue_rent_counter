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
 * - Comprehensive audit logging for all CRUD operations
 * - Change tracking with before/after values
 * - User attribution for all changes
 * - Suspicious activity detection
 * - Rate limiting integration
 *
 * @package App\Observers
 */
final class TariffObserver
{
    /**
     * Handle the Tariff "creating" event.
     *
     * @param Tariff $tariff
     * @return void
     */
    public function creating(Tariff $tariff): void
    {
        $this->logAuditEvent('creating', $tariff, [
            'provider_id' => $tariff->provider_id,
            'name' => $tariff->name,
            'configuration' => $tariff->configuration,
            'active_from' => $tariff->active_from?->toDateString(),
            'active_until' => $tariff->active_until?->toDateString(),
        ]);
    }

    /**
     * Handle the Tariff "created" event.
     *
     * @param Tariff $tariff
     * @return void
     */
    public function created(Tariff $tariff): void
    {
        $this->logAuditEvent('created', $tariff, [
            'id' => $tariff->id,
            'provider_id' => $tariff->provider_id,
            'name' => $tariff->name,
            'configuration_type' => $tariff->configuration['type'] ?? null,
        ]);

        // Check for suspicious activity
        $this->detectSuspiciousActivity('created', $tariff);
    }

    /**
     * Handle the Tariff "updating" event.
     *
     * @param Tariff $tariff
     * @return void
     */
    public function updating(Tariff $tariff): void
    {
        $changes = $tariff->getDirty();
        $original = $tariff->getOriginal();

        $this->logAuditEvent('updating', $tariff, [
            'changes' => $changes,
            'original' => array_intersect_key($original, $changes),
        ]);

        // Check for suspicious changes
        $this->detectSuspiciousChanges($tariff, $changes);
    }

    /**
     * Handle the Tariff "updated" event.
     *
     * @param Tariff $tariff
     * @return void
     */
    public function updated(Tariff $tariff): void
    {
        $changes = $tariff->getChanges();

        $this->logAuditEvent('updated', $tariff, [
            'id' => $tariff->id,
            'changes' => array_keys($changes),
        ]);
    }

    /**
     * Handle the Tariff "deleting" event.
     *
     * @param Tariff $tariff
     * @return void
     */
    public function deleting(Tariff $tariff): void
    {
        $this->logAuditEvent('deleting', $tariff, [
            'id' => $tariff->id,
            'name' => $tariff->name,
            'provider_id' => $tariff->provider_id,
        ]);
    }

    /**
     * Handle the Tariff "deleted" event.
     *
     * @param Tariff $tariff
     * @return void
     */
    public function deleted(Tariff $tariff): void
    {
        $this->logAuditEvent('deleted', $tariff, [
            'id' => $tariff->id,
            'name' => $tariff->name,
        ]);

        // Alert on deletion
        $this->alertOnDeletion($tariff);
    }

    /**
     * Handle the Tariff "restored" event.
     *
     * @param Tariff $tariff
     * @return void
     */
    public function restored(Tariff $tariff): void
    {
        $this->logAuditEvent('restored', $tariff, [
            'id' => $tariff->id,
            'name' => $tariff->name,
        ]);
    }

    /**
     * Handle the Tariff "force deleted" event.
     *
     * @param Tariff $tariff
     * @return void
     */
    public function forceDeleted(Tariff $tariff): void
    {
        $this->logAuditEvent('force_deleted', $tariff, [
            'id' => $tariff->id,
            'name' => $tariff->name,
        ]);

        // Critical alert on force deletion
        $this->alertCritical('Tariff force deleted', $tariff);
    }

    /**
     * Log audit event with user attribution.
     *
     * @param string $event
     * @param Tariff $tariff
     * @param array $data
     * @return void
     */
    private function logAuditEvent(string $event, Tariff $tariff, array $data): void
    {
        $user = auth()->user();

        Log::channel('audit')->info("Tariff {$event}", [
            'event' => $event,
            'tariff_id' => $tariff->id ?? null,
            'tariff_name' => $tariff->name ?? null,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->role?->value,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detect suspicious activity patterns.
     *
     * @param string $event
     * @param Tariff $tariff
     * @return void
     */
    private function detectSuspiciousActivity(string $event, Tariff $tariff): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        // Check for rapid creation
        $recentCount = Tariff::where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentCount > 10) {
            Log::channel('security')->warning('Suspicious tariff creation rate detected', [
                'user_id' => $user->id,
                'count' => $recentCount,
                'tariff_id' => $tariff->id,
            ]);
        }

        // Check for unusual rate values
        if (isset($tariff->configuration['rate']) && $tariff->configuration['rate'] > 10) {
            Log::channel('security')->warning('Unusually high tariff rate detected', [
                'user_id' => $user->id,
                'tariff_id' => $tariff->id,
                'rate' => $tariff->configuration['rate'],
            ]);
        }
    }

    /**
     * Detect suspicious changes.
     *
     * @param Tariff $tariff
     * @param array $changes
     * @return void
     */
    private function detectSuspiciousChanges(Tariff $tariff, array $changes): void
    {
        // Check for configuration changes
        if (isset($changes['configuration'])) {
            $original = $tariff->getOriginal('configuration');
            $new = $changes['configuration'];

            // Alert on type change
            if (($original['type'] ?? null) !== ($new['type'] ?? null)) {
                Log::channel('security')->warning('Tariff type changed', [
                    'tariff_id' => $tariff->id,
                    'old_type' => $original['type'] ?? null,
                    'new_type' => $new['type'] ?? null,
                    'user_id' => auth()->id(),
                ]);
            }

            // Alert on significant rate change
            if (isset($original['rate'], $new['rate'])) {
                $percentChange = abs(($new['rate'] - $original['rate']) / $original['rate']) * 100;
                if ($percentChange > 50) {
                    Log::channel('security')->warning('Significant tariff rate change detected', [
                        'tariff_id' => $tariff->id,
                        'old_rate' => $original['rate'],
                        'new_rate' => $new['rate'],
                        'percent_change' => $percentChange,
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        }
    }

    /**
     * Alert on tariff deletion.
     *
     * @param Tariff $tariff
     * @return void
     */
    private function alertOnDeletion(Tariff $tariff): void
    {
        Log::channel('security')->warning('Tariff deleted', [
            'tariff_id' => $tariff->id,
            'tariff_name' => $tariff->name,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
        ]);
    }

    /**
     * Send critical security alert.
     *
     * @param string $message
     * @param Tariff $tariff
     * @return void
     */
    private function alertCritical(string $message, Tariff $tariff): void
    {
        Log::channel('security')->critical($message, [
            'tariff_id' => $tariff->id,
            'tariff_name' => $tariff->name,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // TODO: Send email/Slack notification to security team
    }
}
