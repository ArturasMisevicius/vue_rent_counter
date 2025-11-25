<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Tariff;
use Illuminate\Support\Facades\Auth;

/**
 * TariffObserver
 * 
 * Audits all tariff changes for compliance and dispute resolution.
 * 
 * Security:
 * - Logs all CRUD operations
 * - Captures user ID and timestamp
 * - Records old and new values
 * - Immutable audit records
 * 
 * Requirements:
 * - Audit trail for billing compliance
 * - Dispute resolution support
 * - Regulatory compliance (financial auditing)
 * 
 * @package App\Observers
 */
class TariffObserver
{
    /**
     * Handle the Tariff "created" event.
     * 
     * Note: We only log 'created' (not 'creating') because auditable_id 
     * is required and only available after the model is saved.
     */
    public function created(Tariff $tariff): void
    {
        $user = Auth::user();
        
        AuditLog::create([
            'tenant_id' => $user?->tenant_id ?? 1, // Tariffs are global, use user's tenant for audit trail
            'auditable_type' => Tariff::class,
            'auditable_id' => $tariff->id,
            'user_id' => Auth::id(),
            'event' => 'created',
            'old_values' => null,
            'new_values' => $tariff->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the Tariff "updated" event.
     */
    public function updated(Tariff $tariff): void
    {
        if ($tariff->wasChanged()) {
            $user = Auth::user();
            
            AuditLog::create([
                'tenant_id' => $user?->tenant_id ?? 1,
                'auditable_type' => Tariff::class,
                'auditable_id' => $tariff->id,
                'user_id' => Auth::id(),
                'event' => 'updated',
                'old_values' => $tariff->getOriginal(),
                'new_values' => $tariff->getChanges(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * Handle the Tariff "deleting" event.
     */
    public function deleting(Tariff $tariff): void
    {
        $user = Auth::user();
        
        AuditLog::create([
            'tenant_id' => $user?->tenant_id ?? 1,
            'auditable_type' => Tariff::class,
            'auditable_id' => $tariff->id,
            'user_id' => Auth::id(),
            'event' => 'deleting',
            'old_values' => $tariff->toArray(),
            'new_values' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the Tariff "deleted" event.
     */
    public function deleted(Tariff $tariff): void
    {
        $user = Auth::user();
        
        AuditLog::create([
            'tenant_id' => $user?->tenant_id ?? 1,
            'auditable_type' => Tariff::class,
            'auditable_id' => $tariff->id,
            'user_id' => Auth::id(),
            'event' => 'deleted',
            'old_values' => $tariff->toArray(),
            'new_values' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the Tariff "restored" event.
     */
    public function restored(Tariff $tariff): void
    {
        $user = Auth::user();
        
        AuditLog::create([
            'tenant_id' => $user?->tenant_id ?? 1,
            'auditable_type' => Tariff::class,
            'auditable_id' => $tariff->id,
            'user_id' => Auth::id(),
            'event' => 'restored',
            'old_values' => null,
            'new_values' => $tariff->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the Tariff "force deleted" event.
     */
    public function forceDeleted(Tariff $tariff): void
    {
        $user = Auth::user();
        
        AuditLog::create([
            'tenant_id' => $user?->tenant_id ?? 1,
            'auditable_type' => Tariff::class,
            'auditable_id' => $tariff->id,
            'user_id' => Auth::id(),
            'event' => 'force_deleted',
            'old_values' => $tariff->toArray(),
            'new_values' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

